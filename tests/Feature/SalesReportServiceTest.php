<?php

namespace Tests\Feature;

use App\Models\Order;
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
        $this->item($usdOrder, $stock, 1, 1, 20, 10);

        $report = app(SalesReportService::class)->summary(new Request(['date' => '2026-09-11']));

        $this->assertSame(-100.0, (float) $report['totals_by_currency']['SYP']['net_sales']);
        $this->assertSame(-1, $report['totals_by_currency']['SYP']['net_qty']);
        $this->assertSame(-60.0, (float) $report['totals_by_currency']['SYP']['net_profit']);
        $this->assertSame(20.0, (float) $report['totals_by_currency']['USD']['net_sales']);
        $this->assertSame(1, $report['totals_by_currency']['USD']['net_qty']);
        $this->assertSame(10.0, (float) $report['totals_by_currency']['USD']['net_profit']);
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
        $dailyHtml = view('includes.orders_template', [
            'seller' => null, 'all_orders' => $dailyReport, 'settings' => [],
            'startDate' => null, 'endDate' => null, 'is_website_order' => false,
        ])->render();
        $this->assertStringContainsString('Gross sales', $dailyHtml);
        $dailyPdf = app('dompdf.wrapper')->loadHTML($dailyHtml)->output();
        $this->assertStringStartsWith('%PDF-', $dailyPdf);
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
