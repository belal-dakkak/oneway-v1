<?php

namespace Tests\Feature;

use App\Models\ClientDebit;
use App\Models\ClientDebtReconciliation;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\User;
use App\Services\ClientAccountService;
use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClientDebtOrderReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('currencies')->updateOrInsert(
            ['name' => 'aed'],
            ['label' => 'AED', 'rate' => 3.675, 'created_at' => now(), 'updated_at' => now()]
        );
        app(CurrencyService::class)->clearRateCache();
    }

    protected function tearDown(): void
    {
        app(CurrencyService::class)->clearRateCache();
        parent::tearDown();
    }

    public function test_full_account_payment_closes_matching_order_and_credits_only_received_cash(): void
    {
        [$seller, $buyer, $account] = $this->accountFixture(100);
        $order = $this->order($seller, $buyer, 100, 'AED', 'MATCHED-100');

        $result = app(ClientAccountService::class)->payAccount($account, 100);

        $this->assertSame(0.0, (float) $account->fresh()->amount);
        $this->assertSame(0.0, (float) $order->fresh()->remain_price);
        $this->assertSame(100.0, (float) $order->fresh()->paid_price);
        $this->assertSame([], $result['settled_order_ids']);
        $this->assertSame(100.0, (float) OrderPayment::query()->sum('pay_amount'));
        $this->assertDatabaseHas('wallet_movements', [
            'user_id' => $seller->id,
            'currency_code' => 'AED',
            'direction' => 'credit',
            'amount' => 100,
        ]);
        $this->assertDatabaseCount('client_debt_reconciliations', 0);
    }

    public function test_account_is_authoritative_and_historical_difference_is_non_cash(): void
    {
        [$seller, $buyer, $account] = $this->accountFixture(100);
        $oldest = $this->order($seller, $buyer, 100, 'AED', 'OLDEST');
        $newest = $this->order($seller, $buyer, 50, 'AED', 'NEWEST');

        $result = app(ClientAccountService::class)->payAccount($account, 100);

        $this->assertSame(0.0, (float) $account->fresh()->amount);
        $this->assertSame(0.0, (float) $oldest->fresh()->remain_price);
        $this->assertSame(0.0, (float) $newest->fresh()->remain_price);
        $this->assertSame(50.0, (float) $newest->fresh()->paid_price);
        $this->assertSame([$newest->id], $result['settled_order_ids']);
        $this->assertSame(50.0, $result['reconciliation_total']);
        $this->assertSame(100.0, (float) OrderPayment::query()->sum('pay_amount'));
        $this->assertDatabaseCount('order_payments', 1);
        $this->assertDatabaseHas('client_debt_reconciliations', [
            'client_debit_id' => $account->id,
            'order_id' => $newest->id,
            'reconciled_amount' => 50,
            'reason' => 'account_settled_after_account_payment',
        ]);
        $this->assertSame(100.0, (float) DB::table('wallet_movements')->sum('amount'));
    }

    public function test_partial_payment_keeps_orders_open_and_does_not_reconcile_difference(): void
    {
        [$seller, $buyer, $account] = $this->accountFixture(100);
        $first = $this->order($seller, $buyer, 75, 'AED', 'PARTIAL-1');
        $second = $this->order($seller, $buyer, 75, 'AED', 'PARTIAL-2');

        $result = app(ClientAccountService::class)->payAccount($account, 50);

        $this->assertSame(50.0, (float) $account->fresh()->amount);
        $this->assertSame(25.0, (float) $first->fresh()->remain_price);
        $this->assertSame(75.0, (float) $second->fresh()->remain_price);
        $this->assertFalse($result['account_closed']);
        $this->assertDatabaseCount('client_debt_reconciliations', 0);
        $this->assertSame(50.0, (float) OrderPayment::query()->sum('pay_amount'));
    }

    public function test_direct_order_payment_uses_the_same_account_reconciliation_and_cash_rules(): void
    {
        [$seller, $buyer, $account] = $this->accountFixture(50);
        $paidOrder = $this->order($seller, $buyer, 50, 'AED', 'DIRECT-PAID');
        $historical = $this->order($seller, $buyer, 25, 'AED', 'DIRECT-HISTORICAL');

        $result = app(ClientAccountService::class)->payOrder($paidOrder, 50);

        $this->assertSame(0.0, (float) $account->fresh()->amount);
        $this->assertSame(0.0, (float) $paidOrder->fresh()->remain_price);
        $this->assertSame(0.0, (float) $historical->fresh()->remain_price);
        $this->assertSame([$historical->id], $result['settled_order_ids']);
        $this->assertSame(50.0, (float) OrderPayment::query()->sum('pay_amount'));
        $this->assertSame(50.0, (float) DB::table('wallet_movements')->sum('amount'));
    }

    public function test_reconciliation_is_isolated_by_shop_customer_and_currency(): void
    {
        [$seller, $buyer, $account] = $this->accountFixture(20);
        $otherSeller = User::factory()->create(['role_id' => User::ROLE_SHOP, 'country_id' => User::COUNTRY_UAE]);
        $otherBuyer = User::factory()->create(['role_id' => User::ROLE_CLIENT, 'country_id' => User::COUNTRY_UAE]);
        $matching = $this->order($seller, $buyer, 30, 'AED', 'ISO-MATCH');
        $differentBuyer = $this->order($seller, $otherBuyer, 30, 'AED', 'ISO-BUYER');
        $differentSeller = $this->order($otherSeller, $buyer, 30, 'AED', 'ISO-SELLER');
        $differentCurrency = $this->order($seller, $buyer, 30, 'USD', 'ISO-USD');

        app(ClientAccountService::class)->payAccount($account, 20);

        $this->assertSame(0.0, (float) $matching->fresh()->remain_price);
        $this->assertSame(30.0, (float) $differentBuyer->fresh()->remain_price);
        $this->assertSame(30.0, (float) $differentSeller->fresh()->remain_price);
        $this->assertSame(30.0, (float) $differentCurrency->fresh()->remain_price);
    }

    public function test_preview_and_apply_reconcile_historical_open_order_without_cash_or_fake_payment(): void
    {
        [$seller, $buyer, $account] = $this->accountFixture(0);
        $order = $this->order($seller, $buyer, 55, 'AED', 'HISTORICAL');
        $report = storage_path('app/test-client-order-reconciliation.json');
        @unlink($report);

        $this->artisan('clients:reconcile-settled-orders', ['--output' => $report])->assertExitCode(0);
        $preview = json_decode(file_get_contents($report), true);
        $this->assertSame(1, $preview['accounts_count']);
        $this->assertSame(1, $preview['orders_count']);
        $this->assertSame(55.0, (float) $order->fresh()->remain_price);

        $this->artisan('clients:reconcile-settled-orders', ['--apply' => $report])->assertExitCode(0);
        $this->assertSame(0.0, (float) $order->fresh()->remain_price);
        $this->assertSame(55.0, (float) $order->fresh()->paid_price);
        $this->assertDatabaseCount('wallet_movements', 0);
        $this->assertDatabaseCount('order_payments', 0);
        $this->assertDatabaseCount('client_debt_reconciliations', 1);

        $this->artisan('clients:reconcile-settled-orders', ['--apply' => $report])->assertExitCode(0);
        $this->assertSame(1, ClientDebtReconciliation::query()->count());
        @unlink($report);
    }

    public function test_apply_rejects_a_stale_report_without_partial_changes(): void
    {
        [$seller, $buyer] = $this->accountFixture(0);
        $firstAccount = ClientDebit::query()->firstOrFail();
        $first = $this->order($seller, $buyer, 20, 'AED', 'STALE-1');

        $buyerTwo = User::factory()->create(['role_id' => User::ROLE_CLIENT, 'country_id' => User::COUNTRY_UAE]);
        ClientDebit::query()->create([
            'creditor_id' => $seller->id,
            'debtor_id' => $buyerTwo->id,
            'amount' => 0,
            'currency_code' => 'AED',
        ]);
        $second = $this->order($seller, $buyerTwo, 30, 'AED', 'STALE-2');
        $report = storage_path('app/test-stale-client-order-reconciliation.json');
        @unlink($report);
        $this->artisan('clients:reconcile-settled-orders', ['--output' => $report])->assertExitCode(0);

        $second->update(['remain_price' => 29]);
        $this->artisan('clients:reconcile-settled-orders', ['--apply' => $report])->assertExitCode(1);

        $this->assertSame(20.0, (float) $first->fresh()->remain_price);
        $this->assertSame(29.0, (float) $second->fresh()->remain_price);
        $this->assertSame(0.0, (float) $firstAccount->fresh()->amount);
        $this->assertDatabaseCount('client_debt_reconciliations', 0);
        @unlink($report);
    }

    private function accountFixture(float $amount): array
    {
        $seller = User::factory()->create(['role_id' => User::ROLE_SHOP, 'country_id' => User::COUNTRY_UAE]);
        $buyer = User::factory()->create(['role_id' => User::ROLE_CLIENT, 'country_id' => User::COUNTRY_UAE]);
        $account = ClientDebit::query()->create([
            'creditor_id' => $seller->id,
            'debtor_id' => $buyer->id,
            'amount' => $amount,
            'currency_code' => 'AED',
        ]);
        return [$seller, $buyer, $account];
    }

    private function order(User $seller, User $buyer, float $remain, string $currency, string $barcode): Order
    {
        return Order::query()->create([
            'seller_id' => $seller->id,
            'buyer_id' => $buyer->id,
            'type' => Order::TYPE_FOR_CLIENT,
            'barcode' => $barcode,
            'curr_type' => $currency,
            'curr_rate' => $currency === 'AED' ? 3.675 : 1,
            'total_price' => $remain,
            'paid_price' => 0,
            'remain_price' => $remain,
        ]);
    }
}
