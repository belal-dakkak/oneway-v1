<?php

namespace Tests\Feature;

use App\Models\ClientDebit;
use App\Models\Order;
use App\Models\UnpaidOrderArchive;
use App\Models\User;
use App\Services\CurrencyService;
use App\Services\SalesReportService;
use App\Services\UnpaidOrdersBaselineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class UnpaidOrdersBaselineTest extends TestCase
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

    public function test_baseline_archives_every_currently_visible_order_without_financial_changes(): void
    {
        [$admin, $seller] = $this->users();
        $this->actingAs($admin);

        $buyer = User::factory()->create(['role_id' => User::ROLE_CLIENT, 'country_id' => User::COUNTRY_UAE]);
        $account = $this->account($seller, $buyer, 75);
        $old = $this->order($seller, $buyer, 75, 'OLD-VISIBLE');

        $missingBuyer = User::factory()->create(['role_id' => User::ROLE_CLIENT, 'country_id' => User::COUNTRY_UAE]);
        $missingAccount = $this->order($seller, $missingBuyer, 20, 'OLD-MISSING-ACCOUNT');

        $ambiguousBuyer = User::factory()->create(['role_id' => User::ROLE_CLIENT, 'country_id' => User::COUNTRY_UAE]);
        $this->account($seller, $ambiguousBuyer, 12);
        $this->account($seller, $ambiguousBuyer, 12);
        $ambiguousAccount = $this->order($seller, $ambiguousBuyer, 12, 'OLD-AMBIGUOUS-ACCOUNT');

        $closedBuyer = User::factory()->create(['role_id' => User::ROLE_CLIENT, 'country_id' => User::COUNTRY_UAE]);
        $this->account($seller, $closedBuyer, 0);
        $alreadyHidden = $this->order($seller, $closedBuyer, 15, 'ALREADY-HIDDEN');

        $service = app(UnpaidOrdersBaselineService::class);
        $report = $service->report();
        $this->assertSame([$old->id, $missingAccount->id, $ambiguousAccount->id], array_column($report['orders'], 'id'));
        $this->assertSame(107.0, (float) $report['remaining_total_by_currency']['AED']);

        $new = $this->order($seller, $buyer, 30, 'NEW-AFTER-PREVIEW');
        $result = $service->apply($report, $admin->id);

        $this->assertSame(3, $result['archived_orders_count']);
        $this->assertSame(0, $result['already_applied_orders_count']);
        $this->assertNotNull($old->fresh()->unpaid_tab_archived_at);
        $this->assertNotNull($missingAccount->fresh()->unpaid_tab_archived_at);
        $this->assertNotNull($ambiguousAccount->fresh()->unpaid_tab_archived_at);
        $this->assertNull($new->fresh()->unpaid_tab_archived_at);
        $this->assertNull($alreadyHidden->fresh()->unpaid_tab_archived_at);
        $this->assertSame(0.0, (float) $old->fresh()->paid_price);
        $this->assertSame(75.0, (float) $old->fresh()->remain_price);
        $this->assertSame(75.0, (float) $account->fresh()->amount);
        $this->assertDatabaseCount('order_payments', 0);
        $this->assertDatabaseCount('wallet_movements', 0);
        $this->assertDatabaseCount('unpaid_order_archives', 3);
        $this->assertDatabaseHas('unpaid_order_archives', [
            'order_id' => $old->id,
            'actor_id' => $admin->id,
            'reason' => 'historical_unpaid_tab_reset',
        ]);

        $unpaid = app(SalesReportService::class)->orders(new Request(), true, [], false);
        $unpaidIds = collect($unpaid['orders'])->pluck('id')->all();
        $this->assertSame([$new->id], $unpaidIds);
        $this->assertSame(1, $unpaid['debt_summary']['orders_count']);
        $this->assertSame(30.0, (float) $unpaid['debt_summary']['totals_by_currency']['AED']['remaining_total']);

        $all = app(SalesReportService::class)->orders(new Request(), false, [], false);
        $allIds = collect($all['orders'])->pluck('id')->all();
        $this->assertContains($old->id, $allIds);
        $this->assertContains($missingAccount->id, $allIds);
        $this->assertContains($ambiguousAccount->id, $allIds);
        $this->assertContains($new->id, $allIds);

        $again = $service->apply($report, $admin->id);
        $this->assertSame(0, $again['archived_orders_count']);
        $this->assertSame(3, $again['already_applied_orders_count']);
        $this->assertSame(3, UnpaidOrderArchive::query()->count());
    }

    public function test_stale_report_is_rejected_without_partial_archiving(): void
    {
        [$admin, $seller] = $this->users();
        $this->actingAs($admin);
        $buyerOne = User::factory()->create(['role_id' => User::ROLE_CLIENT, 'country_id' => User::COUNTRY_UAE]);
        $buyerTwo = User::factory()->create(['role_id' => User::ROLE_CLIENT, 'country_id' => User::COUNTRY_UAE]);
        $this->account($seller, $buyerOne, 20);
        $this->account($seller, $buyerTwo, 30);
        $first = $this->order($seller, $buyerOne, 20, 'STALE-1');
        $second = $this->order($seller, $buyerTwo, 30, 'STALE-2');

        $service = app(UnpaidOrdersBaselineService::class);
        $report = $service->report();
        $second->update(['remain_price' => 29]);

        try {
            $service->apply($report, $admin->id);
            $this->fail('A stale report must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('changed after preview', $exception->getMessage());
        }

        $this->assertNull($first->fresh()->unpaid_tab_archived_at);
        $this->assertNull($second->fresh()->unpaid_tab_archived_at);
        $this->assertDatabaseCount('unpaid_order_archives', 0);
    }

    public function test_command_preview_and_apply_use_a_private_signed_report(): void
    {
        [$admin, $seller] = $this->users();
        $this->actingAs($admin);
        $buyer = User::factory()->create(['role_id' => User::ROLE_CLIENT, 'country_id' => User::COUNTRY_UAE]);
        $this->account($seller, $buyer, 10);
        $order = $this->order($seller, $buyer, 10, 'COMMAND');
        $path = storage_path('app/test-unpaid-orders-baseline.json');
        @unlink($path);

        $this->artisan('orders:baseline-unpaid', ['--output' => $path])->assertExitCode(0);
        $this->assertFileExists($path);
        $this->assertSame(10.0, (float) $order->fresh()->remain_price);

        $this->artisan('orders:baseline-unpaid', ['--apply' => $path])->assertExitCode(0);
        $this->assertNotNull($order->fresh()->unpaid_tab_archived_at);
        $this->assertSame(10.0, (float) $order->fresh()->remain_price);
        @unlink($path);
    }

    public function test_modified_report_is_rejected(): void
    {
        [$admin, $seller] = $this->users();
        $this->actingAs($admin);
        $buyer = User::factory()->create(['role_id' => User::ROLE_CLIENT, 'country_id' => User::COUNTRY_UAE]);
        $this->account($seller, $buyer, 10);
        $order = $this->order($seller, $buyer, 10, 'TAMPERED');

        $service = app(UnpaidOrdersBaselineService::class);
        $report = $service->report();
        $report['orders'][0]['remain_price'] = '0.00';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('report was modified');
        try {
            $service->apply($report, $admin->id);
        } finally {
            $this->assertNull($order->fresh()->unpaid_tab_archived_at);
            $this->assertDatabaseCount('unpaid_order_archives', 0);
        }
    }

    private function users(): array
    {
        $admin = User::factory()->create(['role_id' => User::ROLE_ADMIN, 'country_id' => User::COUNTRY_UAE]);
        $seller = User::factory()->create(['role_id' => User::ROLE_SHOP, 'country_id' => User::COUNTRY_UAE]);
        return [$admin, $seller];
    }

    private function account(User $seller, User $buyer, float $amount): ClientDebit
    {
        return ClientDebit::query()->create([
            'creditor_id' => $seller->id,
            'debtor_id' => $buyer->id,
            'currency_code' => 'AED',
            'amount' => $amount,
        ]);
    }

    private function order(User $seller, User $buyer, float $remain, string $barcode): Order
    {
        return Order::query()->create([
            'seller_id' => $seller->id,
            'buyer_id' => $buyer->id,
            'type' => Order::TYPE_FOR_CLIENT,
            'barcode' => $barcode,
            'curr_type' => 'AED',
            'curr_rate' => 3.675,
            'total_price' => $remain,
            'paid_price' => 0,
            'remain_price' => $remain,
        ]);
    }
}
