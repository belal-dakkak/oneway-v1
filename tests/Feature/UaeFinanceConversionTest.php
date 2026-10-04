<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CurrencyService;
use App\Services\UaeFinanceConversion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UaeFinanceConversionTest extends TestCase
{
    use RefreshDatabase;

    private function user(int $country = 2): User
    {
        return User::create(['name' => 'Finance test', 'email' => uniqid('uae-') . '@example.test',
            'password' => 'x', 'role_id' => User::ROLE_SHOP, 'country_id' => $country]);
    }

    private function fixture(): array
    {
        app(CurrencyService::class)->clearRateCache();
        DB::table('currencies')->updateOrInsert(['name' => 'aed'], ['label' => 'AED', 'rate' => 3.675]);
        $seller = $this->user();
        $buyer = $this->user();
        $foreign = $this->user(4);
        $order = DB::table('orders')->insertGetId(['seller_id' => $seller->id, 'buyer_id' => $buyer->id,
            'barcode' => 'OLD-USD', 'curr_type' => 'USD', 'curr_rate' => 1, 'total_price' => 100,
            'price_without_tax' => 95.24, 'tax_value' => 4.76, 'paid_price' => 20, 'remain_price' => 80]);
        $payment = DB::table('order_payments')->insertGetId(['order_id' => $order, 'pay_amount' => 20, 'exchange_rate' => 1, 'base_amount' => 20]);
        $account = DB::table('client_debits')->insertGetId(['creditor_id' => $seller->id, 'debtor_id' => $buyer->id,
            'currency_code' => 'AED', 'amount' => 80]); // Label was already corrected.
        $aed = DB::table('client_debits')->insertGetId(['creditor_id' => $seller->id, 'debtor_id' => $buyer->id,
            'currency_code' => 'AED', 'amount' => 50]);
        $log = DB::table('client_debit_logs')->insertGetId(['client_debit_id' => $account, 'order_id' => $order,
            'note' => 'old USD debt', 'amount' => 80, 'currency_code' => 'AED', 'exchange_rate' => 1, 'base_amount' => 0]);
        $wallet = DB::table('wallets')->where('user_id', $seller->id)->first();
        DB::table('wallets')->where('id', $wallet->id)->update(['credit' => 20, 'debit' => 0]);
        DB::table('wallet_movements')->insert(['wallet_id' => $wallet->id, 'user_id' => $seller->id,
            'currency_code' => 'USD', 'direction' => 'credit', 'amount' => 20, 'exchange_rate' => 1,
            'base_amount' => 20, 'balance_after' => 20, 'source_type' => \App\Models\OrderPayment::class,
            'source_id' => $payment, 'idempotency_key' => 'old-payment', 'created_at' => now(), 'updated_at' => now()]);
        $foreignOrder = DB::table('orders')->insertGetId(['seller_id' => $foreign->id, 'barcode' => 'SY-KEEP',
            'curr_type' => 'USD', 'curr_rate' => 1, 'total_price' => 50, 'paid_price' => 0, 'remain_price' => 50]);
        $legacy = ['sha256' => str_repeat('a', 64), 'accounts' => [['account_id' => $account, 'shop_id' => $seller->id,
            'customer_id' => $buyer->id, 'source_currency' => 'USD', 'source_amount' => '80.0000']]];
        return compact('seller', 'buyer', 'order', 'payment', 'account', 'aed', 'log', 'wallet', 'foreignOrder', 'legacy');
    }

    public function test_signed_conversion_converts_related_finance_once_and_preserves_foreign_rows(): void
    {
        $f = $this->fixture();
        $service = app(UaeFinanceConversion::class);
        $before = DB::table('orders')->where('id', $f['foreignOrder'])->first();
        $report = $service->report($f['legacy']);
        $this->assertSame([], $report['conflicts']);
        $this->assertDatabaseHas('orders', ['id' => $f['order'], 'total_price' => 100]);
        $this->assertSame('applied', $service->apply($report));
        $this->assertDatabaseHas('orders', ['id' => $f['order'], 'total_price' => 367.50, 'paid_price' => 73.50, 'remain_price' => 294, 'curr_type' => 'AED']);
        $this->assertDatabaseHas('order_payments', ['id' => $f['payment'], 'pay_amount' => 73.50, 'base_amount' => 20]);
        $this->assertEquals(344, DB::table('client_debits')->where('creditor_id', $f['seller']->id)->sum('amount'));
        $this->assertSame(1, DB::table('client_debits')->where('creditor_id', $f['seller']->id)->count());
        $this->assertDatabaseHas('client_debit_logs', ['id' => $f['log'], 'amount' => 294, 'currency_code' => 'AED']);
        $this->assertDatabaseHas('wallet_movements', ['idempotency_key' => 'old-payment', 'amount' => 73.50, 'currency_code' => 'AED']);
        $this->assertEquals($before, DB::table('orders')->where('id', $f['foreignOrder'])->first());
        $count = DB::table('finance_conversion_changes')->count();
        $this->assertGreaterThan(0, $count);
        $this->assertSame('already_applied', $service->apply($report));
        $this->assertSame($count, DB::table('finance_conversion_changes')->count());
    }

    public function test_changed_data_invalidates_report_and_rolls_back_batch_marker(): void
    {
        $f = $this->fixture();
        $service = app(UaeFinanceConversion::class);
        $report = $service->report($f['legacy']);
        DB::table('orders')->where('id', $f['order'])->update(['paid_price' => 21, 'remain_price' => 79]);
        try { $service->apply($report); $this->fail('Expected stale report rejection'); }
        catch (\RuntimeException $e) { $this->assertStringContainsString('changed', $e->getMessage()); }
        $this->assertDatabaseCount('finance_conversion_batches', 0);
        $this->assertDatabaseCount('finance_conversion_changes', 0);
        $this->assertDatabaseHas('orders', ['id' => $f['order'], 'total_price' => 100]);
    }

    public function test_tampered_report_and_unreconciled_cashbox_are_rejected(): void
    {
        $f = $this->fixture();
        $service = app(UaeFinanceConversion::class);
        $report = $service->report($f['legacy']);
        $report['changes'][0]['after'] = '999999';
        try { $service->apply($report); $this->fail('Expected signature rejection'); }
        catch (\RuntimeException $e) { $this->assertStringContainsString('signature', $e->getMessage()); }
        DB::table('wallets')->where('id', $f['wallet']->id)->update(['credit' => 30]);
        $report = $service->report($f['legacy']);
        $this->assertContains('cashbox_totals_do_not_match_recorded_movements', array_column($report['conflicts'], 'reason'));
        $walletConflict = collect($report['conflicts'])->firstWhere('reason', 'cashbox_totals_do_not_match_recorded_movements');
        $this->assertEquals(30, $walletConflict['context']['record']['credit']);
        $this->assertEquals(20, $walletConflict['context']['recorded_credit']);
        $this->assertEquals(10, $walletConflict['context']['unmatched_credit']);
        $this->assertSame(1, $walletConflict['context']['movement_count']);
        $this->assertSame(2, (int) $walletConflict['context']['owners']['user_id']['country_id']);
        $this->assertSame([], array_values(array_filter($report['changes'], fn ($change) =>
            ($change['table'] === 'wallets' && $change['id'] === $f['wallet']->id) || $change['table'] === 'wallet_movements')));
        try { $service->apply($report); $this->fail('Expected conflict rejection'); }
        catch (\RuntimeException $e) { $this->assertStringContainsString('conflicts', $e->getMessage()); }
        $this->assertDatabaseCount('finance_conversion_changes', 0);
    }

    public function test_local_expense_amount_is_preserved_and_original_dollar_posting_is_corrected(): void
    {
        $f = $this->fixture();
        $expense = DB::table('expenses')->insertGetId(['issuer_id' => $f['seller']->id, 'amount' => 36.75, 'description' => 'Local expense']);
        DB::table('wallets')->where('id', $f['wallet']->id)->update(['debit' => 10]);
        DB::table('wallet_movements')->insert(['wallet_id' => $f['wallet']->id, 'user_id' => $f['seller']->id,
            'currency_code' => 'USD', 'direction' => 'debit', 'amount' => 10, 'exchange_rate' => 1,
            'base_amount' => 10, 'balance_after' => 10, 'source_type' => \App\Models\Expense::class,
            'source_id' => $expense, 'idempotency_key' => 'old-expense', 'created_at' => now(), 'updated_at' => now()]);
        $service = app(UaeFinanceConversion::class);
        $report = $service->report($f['legacy']);
        $this->assertSame([], $report['conflicts']);
        $service->apply($report);
        $this->assertDatabaseHas('expenses', ['id' => $expense, 'amount' => 36.75, 'currency_code' => 'AED']);
        $this->assertDatabaseHas('wallet_movements', ['idempotency_key' => 'old-expense', 'amount' => 36.75, 'currency_code' => 'AED']);
    }

    public function test_original_sale_and_website_lines_convert_without_changing_base_prices_stock_or_gateway_evidence(): void
    {
        $f = $this->fixture();
        $category = \App\Models\Category::create(['name' => 'Currency tests']);
        $color = \App\Models\Color::create(['name' => 'Black', 'code' => '#000000']);
        $product = \App\Models\Product::create(['name' => 'Shared shirt', 'barcode' => 'SHARED', 'category_id' => $category->id,
            'country_id' => 3, 'cost_price' => 10, 'retail_price' => 55]);
        $variant = \App\Models\ProductColor::create(['product_id' => $product->id, 'color_id' => $color->id,
            'country_id' => 2, 'barcode' => 'VARIANT', 'sizes' => '[]', 'stock' => 8]);
        $stock = \App\Models\UserProduct::create(['product_color_id' => $variant->id, 'user_id' => $f['seller']->id,
            'country_id' => 2, 'size' => 'M', 'stock' => 8, 'barcode' => 'STOCK', 'wholesale_price' => 10, 'retail_price' => 55]);
        $order = \App\Models\Order::findOrFail($f['order']);
        $order->update(['total_price' => 55, 'paid_price' => 20, 'remain_price' => 35,
            'price_without_tax' => 52.381, 'tax_value' => 2.619]);
        $item = $order->items()->create(['user_product_id' => $stock->id, 'qty' => 0, 'sold_qty' => 1, 'unit_cost' => 10,
            'item_price' => 55, 'total_price' => 0, 'price_without_tax' => 52.381, 'tax_value' => 2.619,
            'item_price_paid' => 55, 'total_price_paid' => 0, 'price_without_tax_paid' => 52.381]);
        $refund = DB::table('refunds')->insertGetId(['order_item_id' => $item->id, 'qty' => 1, 'item_barcode' => 'STOCK', 'order_barcode' => 'OLD-USD',
            'total_price' => 20, 'total_price_paid' => 20, 'net_amount' => 19, 'tax_amount' => 1,
            'cost_amount' => 10, 'currency_code' => 'USD']);
        $website = DB::table('website_orders')->insertGetId(['barcode' => 'WEB-USD', 'country_id' => 2,
            'curr_type' => 'USD', 'curr_rate' => 1, 'total_price' => 55, 'paid_price' => 55, 'remain_price' => 0,
            'gateway_currency' => 'USD', 'gateway_amount' => 55, 'gateway_rate' => 1, 'invoice' => 'actual-charge']);
        DB::table('website_order_items')->insert(['website_order_id' => $website, 'product_color_id' => $variant->id,
            'qty' => 1, 'item_price' => 55, 'total_price' => 55]);
        $service = app(UaeFinanceConversion::class);
        $report = $service->report($f['legacy']);
        $this->assertSame([], $report['conflicts']);
        $service->apply($report);
        $this->assertDatabaseHas('order_items', ['id' => $item->id, 'qty' => 0, 'sold_qty' => 1, 'item_price' => 55, 'item_price_paid' => 202.13]);
        $this->assertDatabaseHas('refunds', ['id' => $refund, 'total_price' => 20, 'total_price_paid' => 73.50,
            'net_amount' => 69.83, 'tax_amount' => 3.67, 'cost_amount' => 36.75]);
        $this->assertDatabaseHas('website_orders', ['id' => $website, 'curr_type' => 'AED', 'total_price' => 202.13,
            'gateway_currency' => 'USD', 'gateway_amount' => 55, 'invoice' => 'actual-charge']);
        foreach ([$order->fresh(), \App\Models\WebsiteOrder::findOrFail($website)] as $converted) {
            $data = app(\App\Services\InvoiceDataService::class)->forOrder($converted);
            $this->assertSame('AED', $data['currency']);
            $this->assertEquals(1, $data['items']->sum('qty'));
            $this->assertEquals(202.13, $data['items']->sum('total_price'));
        }
        $this->assertEquals(8, $stock->fresh()->stock);
        $this->assertEquals(55, $stock->fresh()->retail_price);
        $this->assertEquals(10, $product->fresh()->cost_price);
    }

    public function test_merchant_history_merges_into_existing_aed_account_and_cashbox(): void
    {
        $f = $this->fixture();
        $pair = ['creditor_id' => $f['buyer']->id, 'debtor_id' => $f['seller']->id];
        $old = DB::table('merchant_debits')->insertGetId($pair + ['amount' => -32.65]);
        $aed = DB::table('merchant_debits')->insertGetId($pair + ['amount' => 150, 'currency_code' => 'AED', 'exchange_rate' => 3.675]);
        $payment = DB::table('debit_payments')->insertGetId(['merchant_debit_id' => $old, 'amount' => 10]);
        $log = DB::table('debit_logs')->insertGetId(['merchant_debit_id' => $old, 'debit_payment_id' => $payment, 'amount' => 10, 'note' => 'old payment']);
        $wallet = DB::table('wallets')->insertGetId(['user_id' => $f['seller']->id, 'currency_code' => 'AED', 'credit' => 12, 'debit' => 0]);
        DB::table('wallet_movements')->insert(['wallet_id' => $wallet, 'user_id' => $f['seller']->id,
            'currency_code' => 'AED', 'direction' => 'credit', 'amount' => 12, 'exchange_rate' => 3.675,
            'base_amount' => 3.2653, 'balance_after' => 12, 'idempotency_key' => 'aed-keep', 'created_at' => now()]);
        $service = app(UaeFinanceConversion::class);
        $report = $service->report($f['legacy']);
        $this->assertSame([], $report['conflicts']);
        $service->apply($report);
        $this->assertDatabaseMissing('merchant_debits', ['id' => $old]);
        $this->assertDatabaseHas('merchant_debits', ['id' => $aed, 'amount' => 30.01]);
        $this->assertDatabaseHas('debit_logs', ['id' => $log, 'merchant_debit_id' => $aed, 'amount' => 36.75, 'currency_code' => 'AED']);
        $this->assertDatabaseHas('debit_payments', ['id' => $payment, 'merchant_debit_id' => $aed, 'amount' => 36.75]);
        $this->assertDatabaseHas('wallets', ['id' => $wallet, 'credit' => 85.5]);
        $this->assertDatabaseHas('wallet_movements', ['idempotency_key' => 'aed-keep', 'amount' => 12]);
        $this->assertSame(1, DB::table('wallets')->where('user_id', $f['seller']->id)->count());
    }

    public function test_partial_application_rolls_back_if_a_later_audit_write_fails(): void
    {
        $f = $this->fixture();
        $service = app(UaeFinanceConversion::class);
        $report = $service->report($f['legacy']);
        DB::unprepared("CREATE TRIGGER reject_later_conversion BEFORE INSERT ON finance_conversion_changes
            WHEN NEW.table_name = 'client_debits' BEGIN SELECT RAISE(ABORT, 'simulated audit failure'); END");
        try { $service->apply($report); $this->fail('Expected rollback'); }
        catch (\Illuminate\Database\QueryException $e) { $this->assertStringContainsString('simulated audit failure', $e->getMessage()); }
        finally { DB::unprepared('DROP TRIGGER reject_later_conversion'); }
        $this->assertDatabaseHas('orders', ['id' => $f['order'], 'curr_type' => 'USD', 'total_price' => 100]);
        $this->assertDatabaseCount('finance_conversion_changes', 0);
        $this->assertDatabaseCount('finance_conversion_batches', 0);
    }

    public function test_two_processes_apply_a_report_only_once(): void
    {
        $f = $this->fixture();
        config(['app.key' => 'finance-conversion-concurrency-test']);
        $report = app(UaeFinanceConversion::class)->report($f['legacy']);
        $database = tempnam(sys_get_temp_dir(), 'uae-finance-');
        $paths = [$database, $database . '.json', $database . '.ready1', $database . '.ready2'];
        $pdo = new \PDO('sqlite:' . $database);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $tables = (new \ReflectionClass(UaeFinanceConversion::class))->getConstant('TABLES');
        foreach (array_merge($tables, ['finance_conversion_batches', 'finance_conversion_changes']) as $table) {
            $schema = DB::selectOne("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", [$table]);
            $pdo->exec($schema->sql);
            foreach (DB::table($table)->get() as $row) {
                $values = (array) $row;
                $columns = implode(',', array_map(fn ($name) => '"' . $name . '"', array_keys($values)));
                $marks = implode(',', array_fill(0, count($values), '?'));
                $pdo->prepare('INSERT INTO "' . $table . '" (' . $columns . ') VALUES (' . $marks . ')')->execute(array_values($values));
            }
        }
        file_put_contents($paths[1], json_encode($report, JSON_PRESERVE_ZERO_FRACTION));
        $worker = base_path('tests/Support/UaeFinanceConversionWorker.php');
        $first = new \Symfony\Component\Process\Process([PHP_BINARY, $worker, $database, $paths[1], $paths[2], $paths[3]], base_path());
        $second = new \Symfony\Component\Process\Process([PHP_BINARY, $worker, $database, $paths[1], $paths[3], $paths[2]], base_path());
        try {
            $first->start();
            $second->start();
            $first->wait();
            $second->wait();
            foreach ([$first, $second] as $process) $this->assertTrue($process->isSuccessful(), $process->getErrorOutput() . $process->getOutput());
            $this->assertEqualsCanonicalizing(['applied', 'already_applied'], [trim($first->getOutput()), trim($second->getOutput())]);
            $this->assertEquals(367.50, $pdo->query('SELECT total_price FROM orders WHERE id = ' . $f['order'])->fetchColumn());
            $this->assertEquals(count($report['changes']), $pdo->query('SELECT COUNT(*) FROM finance_conversion_changes')->fetchColumn());
        } finally {
            $first->stop();
            $second->stop();
            $pdo = null;
            foreach ($paths as $path) if (is_file($path)) unlink($path);
        }
    }

    public function test_missing_legacy_tax_metadata_does_not_turn_the_sale_into_vat(): void
    {
        $f = $this->fixture();
        DB::table('orders')->where('id', $f['order'])->update(['price_without_tax' => 0, 'tax_value' => 0]);
        $service = app(UaeFinanceConversion::class);
        $report = $service->report($f['legacy']);
        $this->assertSame([], $report['conflicts']);
        $service->apply($report);
        $this->assertDatabaseHas('orders', ['id' => $f['order'], 'total_price' => 367.50, 'tax_value' => 0]);
    }

    public function test_inconsistent_tax_or_payment_evidence_blocks_the_entire_batch(): void
    {
        $f = $this->fixture();
        DB::table('orders')->where('id', $f['order'])->update(['tax_value' => 15]);
        DB::table('order_payments')->where('id', $f['payment'])->update(['exchange_rate' => 3.675]);
        $service = app(UaeFinanceConversion::class);
        $report = $service->report($f['legacy']);
        $this->assertContains('original_gross_net_tax_do_not_reconcile', array_column($report['conflicts'], 'reason'));
        $this->assertContains('payment_currency_disagrees_with_order', array_column($report['conflicts'], 'reason'));
        $paymentConflict = collect($report['conflicts'])->firstWhere('reason', 'payment_currency_disagrees_with_order');
        $this->assertEquals(3.675, $paymentConflict['context']['record']['exchange_rate']);
        $this->assertSame('USD', $paymentConflict['context']['orders']['curr_type']);
        $this->assertArrayNotHasKey('note', $paymentConflict['context']['record']);
        $this->assertArrayNotHasKey('email', $paymentConflict['context']['owners']['seller_id']);
        try { $service->apply($report); $this->fail('Expected conflicts to block application'); }
        catch (\RuntimeException $e) { $this->assertStringContainsString('conflicts', $e->getMessage()); }
        $this->assertDatabaseCount('finance_conversion_changes', 0);
    }

    public function test_large_order_metadata_is_streamed_and_still_invalidates_a_stale_report(): void
    {
        $f = $this->fixture();
        // Over 128 MiB of stored metadata: retaining get()->map()->keyBy()
        // results or encoding the complete snapshot would exhaust the server limit.
        $note = str_repeat('x', 65536);
        for ($offset = 0; $offset < 2100; $offset += 50) {
            $rows = [];
            for ($i = $offset; $i < $offset + 50; $i++) {
                $rows[] = ['seller_id' => $f['seller']->id, 'barcode' => 'MEMORY-' . $i,
                    'curr_type' => 'AED', 'curr_rate' => 3.675, 'notes' => $note];
            }
            DB::table('orders')->insert($rows);
        }
        unset($rows, $note);
        $service = app(UaeFinanceConversion::class);
        $report = $service->report($f['legacy']);
        $this->assertSame([], $report['conflicts']);
        DB::table('orders')->where('barcode', 'MEMORY-2099')->update(['notes' => 'changed outside retained fields']);
        try { $service->apply($report); $this->fail('Expected complete fingerprint revalidation'); }
        catch (\RuntimeException $e) { $this->assertStringContainsString('changed', $e->getMessage()); }
        $this->assertDatabaseCount('finance_conversion_changes', 0);
        $this->assertDatabaseCount('finance_conversion_batches', 0);
    }

    public function test_old_usd_balance_is_converted_while_verified_later_aed_refunds_are_preserved(): void
    {
        $f = $this->mixedRefundFixture();
        $service = app(UaeFinanceConversion::class);
        $refunds = DB::table('refunds')->orderBy('id')->get()->all();
        $modernLogs = DB::table('client_debit_logs')->whereIn('id', $f['modern_logs'])->orderBy('id')->get()->all();
        $report = $service->report($f['legacy']);
        $this->assertSame([], $report['conflicts']);
        $change = collect($report['changes'])->first(fn ($row) => $row['table'] === 'client_debits' && $row['field'] === 'amount');
        $this->assertEquals(-82.04, $change['before']);
        $this->assertSame('180.00', $change['after']);
        $this->assertSame('360.00', $change['reconciliation']['converted_snapshot_aed']);
        $this->assertEquals(-180, $change['reconciliation']['preserved_refunds_aed']);
        $service->apply($report);
        $this->assertDatabaseHas('client_debits', ['id' => $f['account'], 'amount' => 180, 'currency_code' => 'AED']);
        $this->assertEquals($refunds, DB::table('refunds')->orderBy('id')->get()->all());
        $this->assertEquals($modernLogs, DB::table('client_debit_logs')->whereIn('id', $f['modern_logs'])->orderBy('id')->get()->all());
        $this->assertEquals(360, DB::table('client_debit_logs')->whereNotIn('id', $f['modern_logs'])->sum('amount'));
        $this->assertDatabaseCount('wallet_movements', 0);
        $this->assertSame('already_applied', $service->apply($report));
    }

    public function test_changed_snapshot_remains_blocked_without_matching_refund_evidence(): void
    {
        $f = $this->mixedRefundFixture();
        $service = app(UaeFinanceConversion::class);
        foreach ([[['amount' => -89], 'later_log_amount_or_currency_is_not_verified_aed'],
            [['base_amount' => -25], 'later_log_amount_or_currency_is_not_verified_aed'],
            [['client_refund_id' => null], 'client_refund_link_or_identity_mismatch']] as [$invalid, $expectedReason]) {
            $id = $f['modern_logs'][0];
            $original = (array) DB::table('client_debit_logs')->where('id', $id)->first();
            DB::table('client_debit_logs')->where('id', $id)->update($invalid);
            $report = $service->report($f['legacy']);
            $this->assertContains('original_debt_snapshot_missing_or_balance_changed', array_column($report['conflicts'], 'reason'));
            $conflict = collect($report['conflicts'])->firstWhere('reason', 'original_debt_snapshot_missing_or_balance_changed');
            $this->assertSame($expectedReason, $conflict['context']['refund_reconciliation']['reason']);
            try { $service->apply($report); $this->fail('Expected unresolved evidence to block'); }
            catch (\RuntimeException $e) { $this->assertStringContainsString('conflicts', $e->getMessage()); }
            $this->assertDatabaseHas('client_debits', ['id' => $f['account'], 'amount' => -82.04]);
            $this->assertDatabaseCount('finance_conversion_changes', 0);
            DB::table('client_debit_logs')->where('id', $id)->update($original);
        }
    }

    public function test_refund_recovery_requires_matching_customer_amount_and_unique_source(): void
    {
        $f = $this->mixedRefundFixture();
        $service = app(UaeFinanceConversion::class);
        $links = DB::table('client_refunds')->orderBy('id')->get();
        $cases = [
            ['client_refunds', $links[0]->id, ['client_id' => $f['seller']->id]],
            ['client_refunds', $links[1]->id, ['refund_id' => $links[0]->refund_id]],
            ['refunds', $links[0]->refund_id, ['total_price_paid' => 89]],
            ['refunds', $links[0]->refund_id, ['currency_code' => 'USD']],
        ];
        foreach ($cases as [$table, $id, $invalid]) {
            $original = (array) DB::table($table)->where('id', $id)->first();
            DB::table($table)->where('id', $id)->update($invalid);
            $report = $service->report($f['legacy']);
            $this->assertContains('original_debt_snapshot_missing_or_balance_changed', array_column($report['conflicts'], 'reason'));
            $this->assertFalse(collect($report['changes'])->contains(fn ($row) => $row['table'] === 'client_debits'
                && $row['id'] === $f['account'] && $row['field'] === 'amount'));
            DB::table($table)->where('id', $id)->update($original);
        }
    }

    public function test_real_cross_country_merchant_account_blocks_application_without_changing_either_country(): void
    {
        $f = $this->fixture();
        $merchant = $this->user(1);
        $account = DB::table('merchant_debits')->insertGetId(['creditor_id' => $merchant->id,
            'debtor_id' => $f['seller']->id, 'amount' => 96.80]);
        $service = app(UaeFinanceConversion::class);
        $report = $service->report($f['legacy']);
        $this->assertContains('cross_country_merchant_account', array_column($report['conflicts'], 'reason'));
        $this->assertFalse(collect($report['changes'])->contains(fn ($row) => $row['table'] === 'merchant_debits' && $row['id'] === $account));
        try { $service->apply($report); $this->fail('Cross-country evidence must be resolved first'); }
        catch (\RuntimeException $e) { $this->assertStringContainsString('conflicts', $e->getMessage()); }
        $this->assertDatabaseHas('merchant_debits', ['id' => $account, 'amount' => 96.80, 'currency_code' => null]);
        $this->assertDatabaseHas('users', ['id' => $merchant->id, 'country_id' => 1]);
        $this->assertDatabaseHas('users', ['id' => $f['seller']->id, 'country_id' => 2]);
        $this->assertDatabaseHas('orders', ['id' => $f['order'], 'total_price' => 100, 'curr_type' => 'USD']);
        $this->assertDatabaseCount('finance_conversion_changes', 0);
    }

    private function mixedRefundFixture(): array
    {
        $f = $this->fixture();
        DB::table('wallet_movements')->delete();
        DB::table('wallets')->update(['credit' => 0, 'debit' => 0]);
        DB::table('order_payments')->delete();
        DB::table('client_debits')->where('id', $f['aed'])->delete();
        DB::table('client_debit_logs')->where('id', $f['log'])->delete();
        DB::table('client_debits')->where('id', $f['account'])->update(['amount' => -82.04]);
        $f['legacy']['accounts'][0]['source_amount'] = '97.9600';
        $category = \App\Models\Category::create(['name' => 'Mixed account test']);
        $color = \App\Models\Color::create(['name' => 'Black', 'code' => '#000000']);
        $product = \App\Models\Product::create(['name' => 'Original sale', 'barcode' => 'MIXED', 'category_id' => $category->id, 'country_id' => 2, 'cost_price' => 1]);
        $variant = \App\Models\ProductColor::create(['product_id' => $product->id, 'color_id' => $color->id, 'country_id' => 2,
            'barcode' => 'MIXED-COLOR', 'sizes' => '[]', 'stock' => 2]);
        $stock = \App\Models\UserProduct::create(['product_color_id' => $variant->id, 'user_id' => $f['seller']->id,
            'country_id' => 2, 'size' => 'M', 'stock' => 2, 'barcode' => 'MIXED-STOCK']);
        $f['modern_logs'] = [];
        foreach ([28, 29] as $index => $day) {
            $values = ['seller_id' => $f['seller']->id, 'buyer_id' => $f['buyer']->id, 'barcode' => 'MIXED-' . $index,
                'curr_type' => 'AED', 'curr_rate' => 3.675, 'total_price' => 90, 'paid_price' => 0,
                'remain_price' => 90, 'price_without_tax' => 90, 'tax_value' => 0];
            if ($index === 0) {
                $orderId = $f['order'];
                DB::table('orders')->where('id', $orderId)->update($values);
            } else $orderId = DB::table('orders')->insertGetId($values);
            DB::table('client_debit_logs')->insert(['client_debit_id' => $f['account'], 'order_id' => $orderId,
                'amount' => 48.98, 'currency_code' => 'AED', 'exchange_rate' => 1, 'base_amount' => 0,
                'note' => 'old USD debt after label correction', 'created_at' => "2026-09-{$day} 12:00:00"]);
            $itemId = DB::table('order_items')->insertGetId(['order_id' => $orderId, 'user_product_id' => $stock->id,
                'qty' => 1, 'sold_qty' => 2, 'item_price' => 24.4898]);
            $refundId = DB::table('refunds')->insertGetId(['order_item_id' => $itemId, 'qty' => 1,
                'item_barcode' => 'MIXED-STOCK', 'order_barcode' => $values['barcode'], 'currency_code' => 'AED',
                'total_price' => 24.4898, 'total_price_paid' => 90, 'net_amount' => 90, 'tax_amount' => 0]);
            $clientRefundId = DB::table('client_refunds')->insertGetId(['client_debit_id' => $f['account'],
                'client_id' => $f['buyer']->id, 'refund_id' => $refundId]);
            $f['modern_logs'][] = DB::table('client_debit_logs')->insertGetId(['client_debit_id' => $f['account'],
                'client_refund_id' => $clientRefundId, 'amount' => -90, 'currency_code' => 'AED',
                'exchange_rate' => 3.675, 'base_amount' => -24.4898, 'note' => 'actual AED refund',
                'created_at' => '2026-10-03 12:46:05']);
        }
        return $f;
    }
}
