<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ClientDebit;
use App\Models\OrderItem;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\Refund;
use App\Models\User;
use App\Models\UserProduct;
use App\Services\SalesReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SalesReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpaid_orders_report_excludes_fully_paid_orders(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin', 'email' => 'debts-admin@example.test', 'password' => 'secret',
            'role_id' => User::ROLE_ADMIN, 'country_id' => User::COUNTRY_UAE,
        ]);
        $shop = User::query()->create([
            'name' => 'Shop', 'email' => 'debts-shop@example.test', 'password' => 'secret',
            'role_id' => User::ROLE_SHOP, 'country_id' => User::COUNTRY_UAE,
        ]);
        $this->actingAs($admin);

        $paid = $this->order($shop, 'PAID-ORDER', 'AED', 1, 100, now()->toDateTimeString());
        $unpaid = $this->order($shop, 'UNPAID-ORDER', 'AED', 1, 100, now()->toDateTimeString());
        $unpaid->update(['paid_price' => 60, 'remain_price' => 40]);

        $report = app(SalesReportService::class)->orders(new Request(), true, [], false);
        $ids = collect($report['orders'])->pluck('id')->all();

        $this->assertContains($unpaid->id, $ids);
        $this->assertNotContains($paid->id, $ids);
    }

    public function test_unpaid_report_uses_the_unique_customer_account_as_authority(): void
    {
        $admin = User::factory()->create([
            'role_id' => User::ROLE_ADMIN,
            'country_id' => User::COUNTRY_UAE,
        ]);
        $shop = User::factory()->create([
            'role_id' => User::ROLE_SHOP,
            'country_id' => User::COUNTRY_UAE,
        ]);
        $closedBuyer = User::factory()->create(['role_id' => User::ROLE_CLIENT, 'country_id' => User::COUNTRY_UAE]);
        $openBuyer = User::factory()->create(['role_id' => User::ROLE_CLIENT, 'country_id' => User::COUNTRY_UAE]);
        $missingBuyer = User::factory()->create(['role_id' => User::ROLE_CLIENT, 'country_id' => User::COUNTRY_UAE]);
        $ambiguousBuyer = User::factory()->create(['role_id' => User::ROLE_CLIENT, 'country_id' => User::COUNTRY_UAE]);
        $this->actingAs($admin);

        ClientDebit::query()->create([
            'creditor_id' => $shop->id, 'debtor_id' => $closedBuyer->id,
            'currency_code' => 'AED', 'amount' => 0,
        ]);
        ClientDebit::query()->create([
            'creditor_id' => $shop->id, 'debtor_id' => $openBuyer->id,
            'currency_code' => 'AED', 'amount' => 70,
        ]);
        ClientDebit::query()->create([
            'creditor_id' => $shop->id, 'debtor_id' => $ambiguousBuyer->id,
            'currency_code' => 'AED', 'amount' => 0,
        ]);
        ClientDebit::query()->create([
            'creditor_id' => $shop->id, 'debtor_id' => $ambiguousBuyer->id,
            'currency_code' => 'AED', 'amount' => 0,
        ]);

        $closed = $this->order($shop, 'CLOSED-LEGACY-USD', 'USD', 3.675, 90, now()->toDateTimeString());
        $closed->update(['buyer_id' => $closedBuyer->id, 'paid_price' => 0, 'remain_price' => 90]);
        $open = $this->order($shop, 'OPEN-LEGACY-USD', 'USD', 3.675, 70, now()->toDateTimeString());
        $open->update(['buyer_id' => $openBuyer->id, 'paid_price' => 0, 'remain_price' => 70]);
        $missing = $this->order($shop, 'MISSING-ACCOUNT', 'AED', 3.675, 30, now()->toDateTimeString());
        $missing->update(['buyer_id' => $missingBuyer->id, 'paid_price' => 0, 'remain_price' => 30]);
        $ambiguous = $this->order($shop, 'AMBIGUOUS-ACCOUNT', 'AED', 3.675, 40, now()->toDateTimeString());
        $ambiguous->update(['buyer_id' => $ambiguousBuyer->id, 'paid_price' => 0, 'remain_price' => 40]);

        $report = app(SalesReportService::class)->orders(new Request(), true, [], false);
        $ids = collect($report['orders'])->pluck('id')->all();

        $this->assertNotContains($closed->id, $ids, 'A zero AED account must hide a legacy USD-labelled UAE order.');
        $this->assertSame(90.0, (float) $closed->fresh()->remain_price, 'Listing must not mutate historical orders.');
        $this->assertContains($open->id, $ids);
        $this->assertContains($missing->id, $ids, 'Missing accounts must remain visible for review.');
        $this->assertContains($ambiguous->id, $ids, 'Ambiguous accounts must remain visible for review.');
        $this->assertSame(140.0, (float) $report['total']);
        $this->assertSame(['AED'], array_keys($report['totals_by_currency']));
    }

    public function test_authoritative_account_filter_is_applied_to_pagination_and_totals(): void
    {
        $admin = User::factory()->create(['role_id' => User::ROLE_ADMIN, 'country_id' => User::COUNTRY_UAE]);
        $shop = User::factory()->create(['role_id' => User::ROLE_SHOP, 'country_id' => User::COUNTRY_UAE]);
        $visibleBuyer = User::factory()->create(['role_id' => User::ROLE_CLIENT, 'country_id' => User::COUNTRY_UAE]);
        $closedBuyer = User::factory()->create(['role_id' => User::ROLE_CLIENT, 'country_id' => User::COUNTRY_UAE]);
        $this->actingAs($admin);

        ClientDebit::query()->create([
            'creditor_id' => $shop->id, 'debtor_id' => $closedBuyer->id,
            'currency_code' => 'AED', 'amount' => 0,
        ]);

        foreach (range(1, 11) as $index) {
            $order = $this->order($shop, "VISIBLE-{$index}", 'AED', 3.675, 10, now()->toDateTimeString());
            $order->update(['buyer_id' => $visibleBuyer->id, 'paid_price' => 0, 'remain_price' => 10]);
        }
        foreach (range(1, 3) as $index) {
            $order = $this->order($shop, "HIDDEN-{$index}", 'USD', 3.675, 100, now()->toDateTimeString());
            $order->update(['buyer_id' => $closedBuyer->id, 'paid_price' => 0, 'remain_price' => 100]);
        }

        $report = app(SalesReportService::class)->orders(new Request(), true);

        $this->assertSame(11, $report['orders']->total());
        $this->assertSame(2, $report['orders']->lastPage());
        $this->assertCount(10, $report['orders']->items());
        $this->assertSame(110.0, (float) $report['total']);
    }

    public function test_debt_summary_counts_all_filtered_orders_and_keeps_currencies_separate(): void
    {
        $admin = User::factory()->create(['role_id' => User::ROLE_ADMIN, 'country_id' => User::COUNTRY_SYRIA]);
        $shop = User::factory()->create(['role_id' => User::ROLE_SHOP, 'country_id' => User::COUNTRY_SYRIA]);
        $otherShop = User::factory()->create(['role_id' => User::ROLE_SHOP, 'country_id' => User::COUNTRY_SYRIA]);
        $buyer = User::factory()->create([
            'name' => 'Summary Buyer',
            'role_id' => User::ROLE_CLIENT,
            'country_id' => User::COUNTRY_SYRIA,
        ]);
        $this->actingAs($admin);

        foreach (range(1, 12) as $index) {
            $order = $this->order($shop, "SUMMARY-USD-{$index}", 'USD', 1, 100, now()->toDateTimeString());
            $order->update(['buyer_id' => $buyer->id, 'paid_price' => 75, 'remain_price' => 25]);
        }
        foreach (range(1, 3) as $index) {
            $order = $this->order($shop, "SUMMARY-SYP-{$index}", 'SYP', 1, 1000, now()->toDateTimeString());
            $order->update(['buyer_id' => $buyer->id, 'paid_price' => 500, 'remain_price' => 500]);
        }

        $excluded = $this->order($otherShop, 'SUMMARY-OTHER-SHOP', 'USD', 1, 900, now()->toDateTimeString());
        $excluded->update(['buyer_id' => $buyer->id, 'paid_price' => 0, 'remain_price' => 900]);

        $report = app(SalesReportService::class)->orders(new Request([
            'shop' => $shop->id,
            'buyerName' => 'Summary Buyer',
        ]), true);

        $this->assertSame(15, $report['orders']->total());
        $this->assertSame(2, $report['orders']->lastPage());
        $this->assertCount(10, $report['orders']->items());
        $this->assertSame(15, $report['debt_summary']['orders_count']);
        $this->assertSame([
            'invoice_total' => 3000.0,
            'remaining_total' => 1500.0,
            'orders_count' => 3,
        ], $report['debt_summary']['totals_by_currency']['SYP']);
        $this->assertSame([
            'invoice_total' => 1200.0,
            'remaining_total' => 300.0,
            'orders_count' => 12,
        ], $report['debt_summary']['totals_by_currency']['USD']);
    }

    public function test_debt_summary_groups_legacy_uae_usd_orders_as_aed(): void
    {
        $admin = User::factory()->create(['role_id' => User::ROLE_ADMIN, 'country_id' => User::COUNTRY_UAE]);
        $shop = User::factory()->create(['role_id' => User::ROLE_SHOP, 'country_id' => User::COUNTRY_UAE]);
        $buyer = User::factory()->create(['role_id' => User::ROLE_CLIENT, 'country_id' => User::COUNTRY_UAE]);
        $this->actingAs($admin);

        foreach ([['AED', 100, 40], ['USD', 200, 60]] as [$currency, $total, $remaining]) {
            $order = $this->order($shop, "SUMMARY-{$currency}", $currency, 3.675, $total, now()->toDateTimeString());
            $order->update([
                'buyer_id' => $buyer->id,
                'paid_price' => $total - $remaining,
                'remain_price' => $remaining,
            ]);
        }

        $summary = app(SalesReportService::class)->debtSummary(new Request());

        $this->assertSame(2, $summary['orders_count']);
        $this->assertSame(['AED'], array_keys($summary['totals_by_currency']));
        $this->assertSame(300.0, $summary['totals_by_currency']['AED']['invoice_total']);
        $this->assertSame(100.0, $summary['totals_by_currency']['AED']['remaining_total']);
    }

    public function test_refunds_are_counted_on_refund_day_and_currencies_are_never_merged(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin', 'email' => 'report-admin@example.test', 'password' => 'secret',
            'role_id' => User::ROLE_ADMIN, 'country_id' => User::COUNTRY_SYRIA,
        ]);
        $shop = User::query()->create([
            'name' => 'Shop', 'email' => 'report-shop@example.test', 'password' => 'secret',
            'role_id' => User::ROLE_SHOP, 'country_id' => User::COUNTRY_SYRIA,
        ]);
        $this->actingAs($admin);

        $category = Category::query()->create(['name' => 'Reports']);
        $colorOption = Color::query()->create(['name' => 'Black', 'code' => '#000000']);
        $product = Product::query()->create([
            'name' => 'Report product', 'barcode' => 'RP-1', 'country_id' => User::COUNTRY_SYRIA,
            'category_id' => $category->id, 'cost_price' => 0.4,
        ]);
        $color = ProductColor::query()->create([
            'product_id' => $product->id, 'color_id' => $colorOption->id,
            'country_id' => User::COUNTRY_SYRIA, 'barcode' => 'RPC-1', 'sizes' => '[]', 'stock' => 0,
        ]);
        $stock = UserProduct::query()->create([
            'product_color_id' => $color->id, 'user_id' => $shop->id,
            'country_id' => User::COUNTRY_SYRIA, 'size' => 'M', 'barcode' => 'RPC-M',
            'stock' => 10, 'wholesale_price' => 0.4, 'retail_price' => 1,
        ]);

        $sypOrder = $this->order($shop, 'SYP-1', 'SYP', 100, 200, '2026-09-10 08:00:00');
        $sypItem = $this->item($sypOrder, $stock, 2, 1, 100, 0.4);
        $refund = Refund::query()->create([
            'order_item_id' => $sypItem->id, 'qty' => 1, 'item_price' => 1,
            'total_price' => 1, 'total_price_paid' => 100, 'currency_code' => 'SYP',
            'net_amount' => 100, 'tax_amount' => 0, 'cost_amount' => 40,
            'item_barcode' => 'RPC-M', 'order_barcode' => 'SYP-1',
        ]);
        $refund->forceFill([
            'created_at' => Carbon::parse('2026-09-11 08:00:00'),
            'updated_at' => Carbon::parse('2026-09-11 08:00:00'),
        ])->save();

        $usdOrder = $this->order($shop, 'USD-1', 'USD', 1, 20, '2026-09-11 09:00:00');
        $usdOrder->update(['price_without_tax' => 18, 'tax_value' => 2]);
        $this->item($usdOrder, $stock, 1, 1, 20, 10);

        // The application stores timestamps in Dubai time. 00:30 in Dubai is
        // still the previous calendar day in Syria and must not leak into this
        // report, while 00:30 on the following Dubai day is still Syria's 11th.
        $earlyOrder = $this->order($shop, 'USD-EARLY', 'USD', 1, 1000, '2026-09-11 00:30:00');
        $this->item($earlyOrder, $stock, 1, 1, 1000, 500);
        $lateOrder = $this->order($shop, 'USD-LATE', 'USD', 1, 30, '2026-09-12 00:30:00');
        $lateOrder->update(['price_without_tax' => 30, 'tax_value' => 0]);
        $this->item($lateOrder, $stock, 1, 1, 30, 15);

        $report = app(SalesReportService::class)->summary(new Request(['date' => '2026-09-11']));

        $this->assertSame(-100.0, (float) $report['totals_by_currency']['SYP']['net_sales']);
        $this->assertSame(-1, $report['totals_by_currency']['SYP']['net_qty']);
        $this->assertSame(-60.0, (float) $report['totals_by_currency']['SYP']['net_profit']);
        $this->assertSame(50.0, (float) $report['totals_by_currency']['USD']['net_sales']);
        $this->assertSame(48.0, (float) $report['totals_by_currency']['USD']['gross_net']);
        $this->assertSame(2.0, (float) $report['totals_by_currency']['USD']['gross_tax']);
        $this->assertSame(2, $report['totals_by_currency']['USD']['net_qty']);
        $this->assertSame(25.0, (float) $report['totals_by_currency']['USD']['net_profit']);
        $this->assertSame(0.0, (float) $report['total'], 'Mixed currencies must not be collapsed into one amount.');

        $monthlyReport = $report;
        $monthlyReport['orders'] = $report['rows'];
        $monthlyReport['currency'] = null;
        $html = view('includes.monthly_orders_template', [
            'seller' => null, 'all_orders' => $monthlyReport, 'orders' => $report['rows'],
            'settings' => [], 'startDate' => null, 'endDate' => null,
        ])->render();
        $this->assertStringContainsString('SYP', $html);
        $this->assertStringContainsString('USD', $html);
        $pdf = app('dompdf.wrapper')->loadHTML($html)->output();
        $this->assertStringStartsWith('%PDF-', $pdf);

        $dailyReport = app(SalesReportService::class)->orders(
            new Request(['date' => '2026-09-11']),
            false,
            ['seller', 'buyer', 'items.product.productColor', 'items.refunds'],
            false
        );
        $this->assertSame($dailyReport['orders'], $dailyReport['rows']);
        $dailyHtml = view('includes.orders_template', [
            'seller' => null, 'all_orders' => $dailyReport, 'settings' => [],
            'startDate' => null, 'endDate' => null, 'is_website_order' => false,
        ])->render();
        $this->assertStringContainsString('Gross sales', $dailyHtml);
        $dailyPdf = app('dompdf.wrapper')->loadHTML($dailyHtml)->output();
        $this->assertStringStartsWith('%PDF-', $dailyPdf);
    }

    public function test_profit_report_sums_losses_signed_returns_and_legacy_negative_refunds_once(): void
    {
        $admin = User::factory()->create(['role_id' => User::ROLE_ADMIN, 'country_id' => User::COUNTRY_UAE]);
        $shop = User::factory()->create(['role_id' => User::ROLE_SHOP, 'country_id' => User::COUNTRY_UAE]);
        $otherShop = User::factory()->create(['role_id' => User::ROLE_SHOP, 'country_id' => User::COUNTRY_UAE]);
        $buyer = User::factory()->create(['role_id' => User::ROLE_CLIENT, 'country_id' => User::COUNTRY_UAE]);
        $this->actingAs($admin);

        $category = Category::query()->create(['name' => 'Profit reports']);
        $colorOption = Color::query()->create(['name' => 'Black', 'code' => '#000000']);
        $product = Product::query()->create([
            'name' => 'Profit product', 'barcode' => 'PROFIT-1', 'country_id' => User::COUNTRY_UAE,
            'category_id' => $category->id, 'cost_price' => 40,
        ]);
        $color = ProductColor::query()->create([
            'product_id' => $product->id, 'color_id' => $colorOption->id,
            'country_id' => User::COUNTRY_UAE, 'barcode' => 'PROFIT-C', 'sizes' => '[]', 'stock' => 0,
        ]);
        $stock = UserProduct::query()->create([
            'product_color_id' => $color->id, 'user_id' => $shop->id,
            'country_id' => User::COUNTRY_UAE, 'size' => 'M', 'barcode' => 'PROFIT-M',
            'stock' => 10, 'wholesale_price' => 40, 'retail_price' => 100,
        ]);
        $date = '2026-10-20 12:00:00';

        $profitable = $this->order($shop, 'PROFITABLE', 'AED', 1, 100, $date);
        $profitable->update(['buyer_id' => $buyer->id]);
        $this->item($profitable, $stock, 1, 1, 100, 40);

        $loss = $this->order($shop, 'LOSS', 'AED', 1, 50, $date);
        $loss->update(['buyer_id' => $buyer->id]);
        $this->item($loss, $stock, 1, 1, 50, 80);

        $signedReturn = $this->order($shop, 'SIGNED-RETURN', 'AED', 1, -100, $date);
        $signedReturn->update(['buyer_id' => $buyer->id]);
        $signedReturnItem = $this->item($signedReturn, $stock, -1, -1, 100, 40);
        Refund::query()->create([
            'order_item_id' => $signedReturnItem->id, 'qty' => 1,
            'item_price' => -100, 'total_price' => -100, 'total_price_paid' => -100,
            'currency_code' => 'AED', 'net_amount' => -100, 'tax_amount' => 0, 'cost_amount' => -40,
            'item_barcode' => 'PROFIT-M', 'order_barcode' => 'SIGNED-RETURN',
        ])->forceFill(['created_at' => Carbon::parse($date), 'updated_at' => Carbon::parse($date)])->save();

        $refundedSale = $this->order($shop, 'NEGATIVE-REFUND', 'AED', 1, 100, $date);
        $refundedSale->update(['buyer_id' => $buyer->id]);
        $refundedItem = $this->item($refundedSale, $stock, 1, 0, 100, 40);
        Refund::query()->create([
            'order_item_id' => $refundedItem->id, 'qty' => 1,
            'item_price' => -100, 'total_price' => -100, 'total_price_paid' => -100,
            'currency_code' => 'AED', 'net_amount' => -100, 'tax_amount' => 0, 'cost_amount' => -40,
            'item_barcode' => 'PROFIT-M', 'order_barcode' => 'NEGATIVE-REFUND',
        ])->forceFill(['created_at' => Carbon::parse($date), 'updated_at' => Carbon::parse($date)])->save();

        $excluded = $this->order($otherShop, 'OTHER-SHOP-LOSS', 'AED', 1, -1000, $date);
        $excluded->update(['buyer_id' => $buyer->id]);

        $report = app(SalesReportService::class)->orders(new Request([
            'shop' => $shop->id,
            'buyer' => $buyer->id,
            'date' => '2026-10-20',
        ]), false);

        $this->assertSame(4, $report['orders']->total());
        $this->assertSame(-30.0, (float) $report['profits_by_currency']['AED']);
        $this->assertSame(-30.0, (float) $report['profit']);

        $orders = collect($report['orders']->items())->keyBy('barcode');
        $this->assertSame(60.0, (float) $orders['PROFITABLE']->net_profit);
        $this->assertSame(-30.0, (float) $orders['LOSS']->net_profit);
        $this->assertSame(-60.0, (float) $orders['SIGNED-RETURN']->net_profit);
        $this->assertSame(0.0, (float) $orders['NEGATIVE-REFUND']->net_profit);
        $this->assertSame(-1, (int) $orders['SIGNED-RETURN']->net_qty);
        $this->assertSame(0.0, (float) $orders['SIGNED-RETURN']->refund_total, 'A signed return must not be subtracted twice.');
    }

    private function order(User $shop, string $barcode, string $currency, float $rate, float $total, string $createdAt): Order
    {
        $order = Order::query()->create([
            'seller_id' => $shop->id, 'barcode' => $barcode, 'type' => Order::TYPE_CASH,
            'curr_type' => $currency, 'curr_rate' => $rate, 'total_price' => $total,
            'paid_price' => $total, 'remain_price' => 0,
        ]);
        $order->forceFill(['created_at' => Carbon::parse($createdAt), 'updated_at' => Carbon::parse($createdAt)])->save();
        return $order;
    }

    private function item(Order $order, UserProduct $stock, int $sold, int $remaining, float $paidUnit, float $unitCost): OrderItem
    {
        return OrderItem::query()->create([
            'order_id' => $order->id, 'user_product_id' => $stock->id,
            'qty' => $remaining, 'sold_qty' => $sold, 'unit_cost' => $unitCost,
            'item_price' => $paidUnit / (float) $order->curr_rate,
            'total_price' => $paidUnit * $sold / (float) $order->curr_rate,
            'item_price_paid' => $paidUnit, 'total_price_paid' => $paidUnit * $sold,
            'price_without_tax' => $paidUnit / (float) $order->curr_rate,
            'price_without_tax_paid' => $paidUnit, 'tax_value' => 0, 'tax_value_paid' => 0,
        ]);
    }
}
