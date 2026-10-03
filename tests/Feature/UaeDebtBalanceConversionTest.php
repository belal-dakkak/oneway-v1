<?php

namespace Tests\Feature;

use App\Models\ClientDebit;
use App\Models\User;
use App\Repositories\ClientDebitRepository;
use App\Services\UaeDebtBalanceConversion;
use App\Support\LegacyUaeDebtSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UaeDebtBalanceConversionTest extends TestCase
{
    use RefreshDatabase;

    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $path) if (is_file($path)) unlink($path);
        parent::tearDown();
    }

    public function test_conversion_preserves_history_and_cashboxes_and_can_be_repeated_after_new_payments(): void
    {
        $seller = $this->user();
        $source = [];
        $expected = ['100.0000' => '367.50', '32.6500' => '119.99', '-24.4800' => '-89.96',
            '0.0000' => '0.00', '0.2000' => '0.74', '-0.2000' => '-0.74', '1.2345' => '4.54'];
        foreach ($expected as $old => $target) {
            $account = $this->account($seller, $old);
            $source[] = [$account, 'USD', $old];
            DB::table('client_debit_logs')->insert(['client_debit_id' => $account->id, 'note' => 'Historical payment',
                'amount' => '-10.0000', 'currency_code' => 'AED', 'exchange_rate' => 1, 'base_amount' => 0]);
            DB::table('client_debit_payments')->insert(['client_debit_id' => $account->id,
                'amount' => '10.0000', 'exchange_rate' => 1, 'base_amount' => 0]);
        }
        $existingAed = $this->account($seller, '75.12');
        $source[] = [$existingAed, 'AED', '75.1200'];
        $newAccount = $this->account($seller, '250.00'); // Not in the old export.
        $orderId = DB::table('orders')->insertGetId(['seller_id' => $seller->id, 'buyer_id' => $source[0][0]->debtor_id,
            'barcode' => 'KEEP-ORIGINAL', 'curr_type' => 'AED', 'curr_rate' => '3.675',
            'total_price' => 500, 'paid_price' => 100, 'remain_price' => 400]);
        DB::table('order_payments')->insert(['order_id' => $orderId, 'pay_amount' => 100,
            'exchange_rate' => '3.675', 'base_amount' => '27.2109']);
        DB::table('wallets')->where('user_id', $seller->id)->update(['credit' => 500, 'debit' => 100]);
        $before = $this->history();
        $snapshot = $this->source($source);
        $service = app(UaeDebtBalanceConversion::class);
        $preview = $service->convert($snapshot);
        $this->assertFalse($preview['applied']);
        $this->assertFalse($preview['blocked']);
        $this->assertSame(6, $preview['counts']['ready']);
        $this->assertEquals($before, $this->history());
        $result = $service->convert($snapshot, true);
        $this->assertSame(6, $result['counts']['converted']);
        $this->assertSame(1, $result['counts']['zero_unchanged']);
        $this->assertSame(1, $result['counts']['excluded_aed']);
        foreach (array_slice($source, 0, 7) as [$account, $currency, $old]) {
            $this->assertEquals($expected[$old], $account->fresh()->amount);
            $logs = app(ClientDebitRepository::class)->getDebitLogs($account->id, new Request(), false)->get();
            $corrections = $logs->filter(fn ($log) => strpos($log->note, UaeDebtBalanceConversion::MARKER) === 0);
            $this->assertCount($old === '0.0000' ? 0 : 1, $corrections);
            if ($corrections->isNotEmpty()) {
                $log = $corrections->first();
                $this->assertTrue(\Brick\Math\BigDecimal::of($expected[$old])->minus($old)->isEqualTo((string) $log->amount));
                $this->assertSame('AED', $log->currency_code);
                $this->assertStringContainsString($old . ' USD', $log->note);
            }
        }
        $this->assertEquals(75.12, $existingAed->fresh()->amount);
        $this->assertEquals(250, $newAccount->fresh()->amount);
        $after = $this->history();
        foreach (['orders', 'order_payments', 'client_debit_payments', 'wallets', 'wallet_movements', 'refunds', 'client_refunds'] as $table) {
            $this->assertEquals($before[$table], $after[$table], $table . ' must remain unchanged');
        }
        $this->assertEquals($before['client_debit_logs'], array_slice($after['client_debit_logs'], 0, 7));
        $this->assertSame(6, $service->convert($snapshot, true)['counts']['already_applied']);
        $this->assertEquals($after, $this->history());
        DB::table('client_debits')->where('id', $source[0][0]->id)->decrement('amount', 10);
        $afterPayment = $this->history();
        $this->assertSame(6, $service->convert($snapshot, true)['counts']['already_applied']);
        $this->assertEquals($afterPayment, $this->history());
    }

    /** @dataProvider conflicts */
    public function test_a_conflict_prevents_the_entire_batch(string $conflict, string $reason): void
    {
        $seller = $this->user();
        $valid = $this->account($seller, '100');
        $invalid = $this->account($seller, '32.65');
        $source = $this->source([[$valid, 'USD', '100.0000'], [$invalid, 'USD', '32.6500']]);
        if ($conflict === 'balance') $invalid->update(['amount' => 30]);
        if ($conflict === 'currency') $invalid->update(['currency_code' => 'USD']);
        if ($conflict === 'identity') $invalid->update(['debtor_id' => $this->user()->id]);
        if ($conflict === 'country') $seller->update(['country_id' => User::COUNTRY_SYRIA]);
        if ($conflict === 'missing') $invalid->delete();
        if ($conflict === 'marker') DB::table('client_debit_logs')->insert(['client_debit_id' => $invalid->id,
            'note' => UaeDebtBalanceConversion::MARKER . $invalid->id . '|unexpected', 'amount' => 1, 'currency_code' => 'AED']);
        $before = $this->history();
        $report = app(UaeDebtBalanceConversion::class)->convert($source, true);
        $this->assertTrue($report['blocked']);
        $this->assertFalse($report['applied']);
        $this->assertSame($reason, collect($report['rows'])->firstWhere('account_id', $invalid->id)['reason']);
        $this->assertEquals($before, $this->history());
    }

    public static function conflicts(): array
    {
        return [['balance', 'balance_changed_since_export'], ['currency', 'expected_aed_label'],
            ['identity', 'account_identity_changed'], ['country', 'not_uae_shop'],
            ['missing', 'missing_account'], ['marker', 'conversion_marker_conflict']];
    }

    public function test_insert_failure_rolls_back_preceding_balance_updates_and_markers(): void
    {
        $seller = $this->user();
        $first = $this->account($seller, '100');
        $second = $this->account($seller, '200');
        $snapshot = $this->source([[$first, 'USD', '100.0000'], [$second, 'USD', '200.0000']]);
        DB::unprepared('CREATE TEMP TRIGGER reject_second_correction BEFORE INSERT ON client_debit_logs '
            . 'WHEN NEW.client_debit_id = ' . $second->id . " BEGIN SELECT RAISE(ABORT, 'test insert failure'); END");
        $before = $this->history();
        try {
            app(UaeDebtBalanceConversion::class)->convert($snapshot, true);
            $this->fail('Expected the insert failure');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertStringContainsString('test insert failure', $exception->getMessage());
        }
        $this->assertEquals($before, $this->history());
    }

    public function test_command_defaults_to_preview_and_returns_failure_for_conflicts(): void
    {
        $account = $this->account($this->user(), '100');
        $this->source([[$account, 'USD', '100.0000']]);
        $path = end($this->files);
        $this->artisan('clients:convert-uae-debt-balances', ['--source' => $path])->assertExitCode(0);
        $this->assertEquals(100, $account->fresh()->amount);
        $account->update(['amount' => 101]);
        $this->artisan('clients:convert-uae-debt-balances', ['--source' => $path, '--apply' => true])->assertExitCode(1);
        $this->assertEquals(101, $account->fresh()->amount);
        $account->update(['amount' => 100]);
        $this->artisan('clients:convert-uae-debt-balances', ['--source' => $path, '--apply' => true])->assertExitCode(0);
        $this->assertEquals(367.50, $account->fresh()->amount);
        $this->artisan('clients:convert-uae-debt-balances')->assertExitCode(1);
    }

    public function test_two_concurrent_processes_cannot_convert_the_same_balance_twice(): void
    {
        $account = $this->account($this->user(), '100');
        $this->source([[$account, 'USD', '100.0000']]);
        $sourcePath = end($this->files);
        $database = tempnam(sys_get_temp_dir(), 'uae-concurrent-');
        $this->files[] = $database;
        $ready1 = $database . '.ready1';
        $ready2 = $database . '.ready2';
        $this->files[] = $ready1;
        $this->files[] = $ready2;
        $pdo = new \PDO('sqlite:' . $database);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, country_id INTEGER);
            CREATE TABLE client_debits (id INTEGER PRIMARY KEY, creditor_id INTEGER, debtor_id INTEGER,
                amount NUMERIC, currency_code TEXT, updated_at TEXT);
            CREATE TABLE client_debit_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, client_debit_id INTEGER,
                amount NUMERIC, currency_code TEXT, exchange_rate NUMERIC, base_amount NUMERIC,
                note TEXT, created_at TEXT, updated_at TEXT)');
        $pdo->prepare('INSERT INTO users (id, country_id) VALUES (?, 2)')->execute([$account->creditor_id]);
        $pdo->prepare("INSERT INTO client_debits (id, creditor_id, debtor_id, amount, currency_code) VALUES (?, ?, ?, 100, 'AED')")
            ->execute([$account->id, $account->creditor_id, $account->debtor_id]);
        $worker = base_path('tests/Support/UaeDebtConversionWorker.php');
        $first = new \Symfony\Component\Process\Process([PHP_BINARY, $worker, $database, $sourcePath, $ready1, $ready2], base_path());
        $second = new \Symfony\Component\Process\Process([PHP_BINARY, $worker, $database, $sourcePath, $ready2, $ready1], base_path());
        try {
            $first->start();
            $second->start();
            $first->wait();
            $second->wait();
            foreach ([$first, $second] as $process) {
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput() . $process->getOutput());
            }
            $results = [json_decode(trim($first->getOutput()), true), json_decode(trim($second->getOutput()), true)];
            $this->assertEqualsCanonicalizing([['converted' => 1], ['already_applied' => 1]], $results);
            $this->assertEquals(367.50, $pdo->query('SELECT amount FROM client_debits')->fetchColumn());
            $this->assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM client_debit_logs')->fetchColumn());
        } finally {
            $first->stop();
            $second->stop();
            $pdo = null;
        }
    }

    private function user(): User
    {
        return User::create(['name' => 'Conversion test', 'email' => uniqid('convert-') . '@example.test',
            'password' => 'x', 'role_id' => User::ROLE_SHOP, 'country_id' => User::COUNTRY_UAE]);
    }

    private function account(User $seller, string $amount): ClientDebit
    {
        return ClientDebit::create(['creditor_id' => $seller->id, 'debtor_id' => $this->user()->id,
            'amount' => $amount, 'currency_code' => 'AED']);
    }

    private function source(array $rows): array
    {
        $path = tempnam(sys_get_temp_dir(), 'uae-debt-test-');
        $this->files[] = $path;
        $values = array_map(function ($entry) {
            [$account, $currency, $amount] = $entry;
            $usd = $currency === 'USD' ? $amount : '0.0000';
            $aed = $currency === 'AED' ? $amount : '0.0000';
            return "({$account->creditor_id}, {$account->debtor_id}, '{$account->id}: {$currency} = {$amount}', {$usd}, {$aed})";
        }, $rows);
        file_put_contents($path, 'INSERT INTO `d` (`shop_id`, `customer_id`, `accounts`, `usd_labelled_amount`, `aed_amount`) VALUES '
            . implode(",\n", $values) . ';');
        return app(LegacyUaeDebtSnapshot::class)->read($path);
    }

    private function history(): array
    {
        $result = [];
        foreach (['client_debits', 'client_debit_logs', 'client_debit_payments', 'orders', 'order_payments',
            'wallets', 'wallet_movements', 'refunds', 'client_refunds'] as $table) {
            $result[$table] = DB::table($table)->orderBy('id')->get()->all();
        }
        return $result;
    }
}
