<?php

namespace Tests\Feature;

use App\Models\ClientDebit;
use App\Models\User;
use App\Services\UaeClientCurrencyLabels;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UaeClientCurrencyLabelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_labels_change_without_recalculating_any_amount_including_credits_and_zero_balances(): void
    {
        $uae = $this->user(2);
        $syria = $this->user(4);
        foreach ([[32.65, $uae], [-24.48, $uae], [0, $uae], [57.14, $uae], [100, $syria]] as [$amount, $seller]) {
            $account = ClientDebit::create(['creditor_id' => $seller->id, 'debtor_id' => $this->user(2)->id,
                'amount' => $amount, 'currency_code' => 'USD']);
            DB::table('client_debit_logs')->insert(['client_debit_id' => $account->id,
                'amount' => $amount, 'currency_code' => 'USD', 'exchange_rate' => 1, 'base_amount' => 0,
                'note' => 'Historical entry', 'created_at' => '2026-04-27 17:45:07']);
            DB::table('client_debit_payments')->insert(['client_debit_id' => $account->id, 'amount' => 10,
                'exchange_rate' => 1, 'base_amount' => 0]);
        }
        $before = $this->snapshot();
        $service = app(UaeClientCurrencyLabels::class);
        $this->assertSame(['accounts' => 4, 'logs' => 4, 'applied' => false], $service->correct());
        $this->assertEquals($before, $this->snapshot());
        $this->assertSame(['accounts' => 4, 'logs' => 4, 'applied' => true], $service->correct(true));
        $after = $this->snapshot();
        foreach (['client_debits', 'client_debit_logs'] as $table) {
            foreach ($after[$table] as $index => $row) {
                $this->assertSame($index < 4 ? 'AED' : 'USD', $row->currency_code);
                $row->currency_code = $before[$table][$index]->currency_code;
            }
        }
        $this->assertEquals($before, $after, 'Only the two currency label columns may change.');
        $this->assertSame(['accounts' => 0, 'logs' => 0, 'applied' => true], $service->correct(true));
    }

    public function test_existing_aed_account_for_the_same_pair_stops_the_entire_correction(): void
    {
        $seller = $this->user(2);
        $buyer = $this->user(2);
        foreach (['USD', 'AED'] as $currency) {
            ClientDebit::create(['creditor_id' => $seller->id, 'debtor_id' => $buyer->id,
                'amount' => 100, 'currency_code' => $currency]);
        }
        $before = $this->snapshot();
        try {
            app(UaeClientCurrencyLabels::class)->correct(true);
            $this->fail('Conflicting accounts must not be silently merged.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Duplicate accounts', $exception->getMessage());
        }
        $this->assertEquals($before, $this->snapshot());
    }

    private function user(int $country): User
    {
        return User::create(['name' => 'Currency label test', 'email' => uniqid('label-') . '@example.test',
            'password' => 'x', 'role_id' => User::ROLE_SHOP, 'country_id' => $country]);
    }

    private function snapshot(): array
    {
        $tables = ['client_debits', 'client_debit_logs', 'client_debit_payments', 'orders', 'order_payments', 'wallets', 'wallet_movements'];
        $result = [];
        foreach ($tables as $table) $result[$table] = DB::table($table)->orderBy('id')->get()->all();
        return $result;
    }
}
