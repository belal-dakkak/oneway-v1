<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Color;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\User;
use App\Models\UserProduct;
use App\Models\WalletMovement;
use App\Repositories\RefundRepository;
use App\Services\InvoiceDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvoiceOriginalSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_wholesale_video_prices_and_totals_match_on_every_invoice_output(): void
    {
        $order = $this->sale('complex_from_multi', 'AED', [[5, 105, 100], [10, 210, 200]]);
        $this->assertOutputs($order, [[5, 100, 500, 25, 525], [10, 200, 2000, 100, 2100]], 'wholesale');
        $this->assertSame(2625.0, (float) $order->total_price);
        $this->assertSame(2500.0, (float) $order->price_without_tax);
        $this->assertSame(125.0, (float) $order->tax_value);
    }

    public function test_fully_refunded_quick_sale_keeps_its_original_55_aed_invoice(): void
    {
        $order = $this->sale('simple', 'AED', [[1, 55, 52.381]]);
        $this->actingAs($order->seller);
        $item = $order->items()->first();
        $this->refund($item->id, 1, '55.00');
        $this->assertSame(0, $item->fresh()->qty);
        $this->assertSame(0.0, (float) $item->fresh()->total_price);
        $this->assertOutputs($order->fresh(), [[1, 55, 52.38, 2.62, 55]], 'quick-55-refunded');
        $this->assertSame(55.0, (float) $order->fresh()->total_price);
    }

    public function test_partial_custom_and_zero_refunds_do_not_reprice_original_invoices_or_fees(): void
    {
        foreach (['USD', 'SYP'] as $currency) {
            $gross = $currency === 'SYP' ? 105000 : 105;
            $net = $currency === 'SYP' ? 100000 : 100;
            $order = $this->sale('complex_from_multi', $currency, [[2, $gross, $net]]);
            $order->update(['shipping_fee' => 10, 'cod_fee' => 5, 'discount' => 3,
                'total_price' => $gross * 2 + 12, 'paid_price' => $gross * 2 + 12]);
            $this->actingAs($order->seller);
            $item = $order->items()->first();
            $this->refund($item->id, 1, $currency === 'SYP' ? '30000' : '30.00');
            $this->assertSame(1, $item->fresh()->qty);
            $expected = [[2, $net, $net * 2, ($gross - $net) * 2, $gross * 2]];
            $this->assertOutputs($order->fresh(), $expected, $currency . '-partial');
            $movements = WalletMovement::query()->count();
            $this->refund($item->id, 1, '0');
            $this->assertSame(0, $item->fresh()->qty);
            $this->assertSame($movements, WalletMovement::query()->count());
            $this->assertOutputs($order->fresh(), $expected, $currency . '-full-zero');
            $this->assertSame(10.0, (float) $order->fresh()->shipping_fee);
            $this->assertSame(5.0, (float) $order->fresh()->cod_fee);
        }
    }

    public function test_legacy_sale_quantities_are_recovered_in_one_query_without_guessing(): void
    {
        $order = $this->sale('simple', 'AED', [[2, 55, 52.381], [1, 150, 142.8571]]);
        $this->actingAs($order->seller);
        foreach ($order->items as $item) {
            $item->update(['sold_qty' => null]);
            $this->refund($item->id, 1, '0');
        }
        $order = $order->fresh()->load('items.user_product.productColor.product');
        DB::enableQueryLog();
        DB::flushQueryLog();
        $items = app(InvoiceDataService::class)->forOrder($order)['items'];
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertCount(1, $queries);
        $this->assertStringContainsString('refunds', $queries[0]['query']);
        $this->assertSame([2, 1], $items->pluck('qty')->all());
        $this->assertSame([110.0, 150.0], $items->pluck('total_price')->all());
        $this->assertOutputs($order, [[2, 55, 104.76, 5.24, 110], [1, 150, 142.86, 7.14, 150]], 'legacy');
    }

    public function test_client_sale_still_displays_its_entered_tax_inclusive_price(): void
    {
        $order = $this->sale('complex', 'AED', [[1, 150, 142.8571]]);
        $this->assertOutputs($order, [[1, 150, 142.86, 7.14, 150]], 'client-150');
    }

    public function test_direct_print_lookup_keeps_country_access_checks(): void
    {
        $order = $this->sale('simple', 'AED', [[1, 55, 52.381]]);
        $otherCountry = User::query()->create([
            'name' => 'Other country', 'email' => 'other-country@example.test', 'password' => 'secret',
            'role_id' => User::ROLE_SHOP, 'country_id' => User::COUNTRY_SYRIA,
        ]);
        $this->actingAs($otherCountry)->get(route('orders.print-info', $order->id))->assertNotFound();
        $this->actingAs($order->seller)->get(route('orders.print-info', 999999))->assertNotFound();
    }

    public function test_direct_receipt_has_only_real_rows_for_one_two_and_three_products_and_customer_trn(): void
    {
        foreach ([1, 2, 3] as $count) {
            $order = $this->sale('simple', 'AED', array_fill(0, $count, [1, 55, 52.381]));
            // Distinct stored products must stay distinct in the direct-print payload.
            foreach ($order->items as $index => $item) {
                $product = $item->user_product->productColor->product->replicate();
                $product->name = 'Long real product name ' . $index;
                $product->barcode = 'DISTINCT-' . $count . '-' . $index;
                $product->save();
                $item->user_product->productColor->update(['product_id' => $product->id]);
            }
            $buyer = User::query()->create([
                'name' => 'Buyer', 'email' => 'trn-' . $count . '@example.test', 'password' => 'secret',
                'country_id' => User::COUNTRY_UAE, 'role_id' => User::ROLE_CLIENT, 'trn' => 'CUSTOMER-TRN-' . $count,
            ]);
            $order->update(['buyer_id' => $buyer->id, 'trn' => ' ']);
            $this->actingAs($order->seller);
            $response = $this->getJson(route('orders.print-info', $order->id))->assertOk()
                ->assertJsonCount($count, 'products')->assertJsonPath('total_count', $count)
                ->assertJsonPath('total_model_count', $count)
                ->assertJsonPath('products_count', $count)
                ->assertJsonPath('compact_layout', $count <= 2)
                ->assertJsonPath('customer_trn', $buyer->trn);
            foreach ($response->json('products') as $product) {
                $this->assertSame(1, $product['qty']);
                $this->assertNotSame('', trim($product['name']));
            }
            foreach (['invoice.typed.show', 'invoice.typed.printv2'] as $route) {
                $this->get(route($route, ['source' => 'order', 'id' => $order->id]))->assertOk()->assertSee($buyer->trn);
            }
            $order->update(['trn' => 'ORIGINAL-TRN']);
            $this->getJson(route('orders.print-info', $order->id))->assertJsonPath('customer_trn', 'ORIGINAL-TRN');
            $this->assertSame('ORIGINAL-TRN', app(InvoiceDataService::class)->forOrder($order->fresh())['customerTrn']);
        }
    }

    public function test_new_orders_snapshot_customer_trn_and_keep_it_after_profile_changes(): void
    {
        $buyer = User::query()->create([
            'name' => 'Buyer', 'email' => 'snapshot@example.test', 'password' => 'secret',
            'country_id' => User::COUNTRY_UAE, 'role_id' => User::ROLE_CLIENT, 'trn' => 'SAVED-TRN',
        ]);
        foreach ([Order::class, \App\Models\WebsiteOrder::class] as $class) {
            $order = $class::query()->create(['seller_id' => $buyer->id, 'buyer_id' => $buyer->id, 'barcode' => uniqid('SNAPSHOT-'), 'country_id' => 2]);
            $this->assertSame('SAVED-TRN', $order->fresh()->trn);
            $buyer->update(['trn' => 'CHANGED-TRN']);
            $this->assertSame('SAVED-TRN', app(InvoiceDataService::class)->customerTrn($order->fresh()));
            $buyer->update(['trn' => 'SAVED-TRN']);
        }
    }

    private function refund(int $itemId, int $qty, string $price): void
    {
        DB::transaction(fn () => app(RefundRepository::class)->add(Request::create('/admin/refunds', 'POST', [
            'selected_products' => [['product_id' => $itemId, 'qty' => $qty, 'price' => $price]],
        ])));
    }

    // Expected line: quantity, entered unit price, net amount, VAT, gross amount.
    private function assertOutputs(Order $order, array $expected, string $name): void
    {
        $this->actingAs($order->seller);
        $beforeOrder = $order->fresh()->getAttributes();
        $beforeItems = $order->items()->get()->map->getAttributes()->all();
        $beforeMovements = WalletMovement::query()->count();
        $items = app(InvoiceDataService::class)->forOrder($order)['items'];
        $this->assertEquals($expected, $items->map(fn ($item) => [
            $item->qty, $item->entered_unit_price, $item->line_price_without_tax, $item->line_tax_value, $item->total_price,
        ])->all());
        foreach (['invoice.typed.show' => 'a4', 'invoice.typed.printv2' => 'receipt'] as $route => $format) {
            $response = $this->get(route($route, ['source' => 'order', 'id' => $order->id]))->assertOk();
            $response->assertViewHas('items', fn ($rendered) => $rendered->pluck('qty')->all() === $items->pluck('qty')->all());
            $html = $response->getContent();
            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            $xpath = new \DOMXPath($dom);
            $rows = $xpath->query('//table[contains(concat(" ", normalize-space(@class), " "), " items ")]/tbody/tr[td]');
            foreach ($expected as $index => $line) {
                $cells = $xpath->query('./td', $rows->item($index));
                $this->assertSame((string) $line[0], trim($cells->item($format === 'receipt' ? 1 : 2)->textContent));
                $rate = $format === 'receipt' ? $line[1] : $items[$index]->price_without_tax;
                $this->assertStringContainsString(number_format($rate, $order->curr_type === 'SYP' ? 0 : 2), $cells->item($format === 'receipt' ? 2 : 3)->textContent);
            }
            if ($directory = getenv('INVOICE_ORIGINAL_OUTPUT_DIR')) file_put_contents($directory . '/' . $name . '-' . $format . '.html', $html);
        }
        $json = $this->get(route('orders.print-info', $order->id))->assertOk()->json();
        $this->assertEquals($items->sum('qty'), $json['total_count']);
        $this->assertSame($items->count(), $json['total_model_count']);
        foreach ($expected as $index => $line) {
            $this->assertEquals($line[0], $json['products'][$index]['qty']);
            $this->assertEquals($line[1], $json['products'][$index]['item_price']);
            $this->assertEquals($line[4], (float) $json['products'][$index]['total_price']);
        }
        $pdf = $this->get(route('download.invoice.typed', ['source' => 'order', 'id' => $order->id]))
            ->assertOk()->assertHeader('content-type', 'application/pdf')->getContent();
        $this->assertStringStartsWith('%PDF-', $pdf);
        if ($directory = getenv('INVOICE_ORIGINAL_OUTPUT_DIR')) file_put_contents($directory . '/' . $name . '.pdf', $pdf);
        $this->assertSame($beforeOrder, $order->fresh()->getAttributes());
        $this->assertSame($beforeItems, $order->items()->get()->map->getAttributes()->all());
        $this->assertSame($beforeMovements, WalletMovement::query()->count());
    }

    private function sale(string $type, string $currency, array $lines): Order
    {
        $country = $currency === 'AED' ? User::COUNTRY_UAE : User::COUNTRY_SYRIA;
        $seller = User::query()->create([
            'name' => 'Original invoice seller', 'email' => uniqid('invoice-', true) . '@example.test',
            'password' => 'secret', 'role_id' => User::ROLE_SHOP, 'country_id' => $country,
            'enable_tax' => 'yes', 'tax_ratio' => 5, 'trn' => '1048430610003',
        ]);
        $decimals = $currency === 'SYP' ? 0 : 2;
        $gross = collect($lines)->sum(fn ($line) => round($line[0] * $line[1], $decimals));
        $net = collect($lines)->sum(fn ($line) => round($line[0] * $line[2], $decimals));
        $order = Order::query()->create([
            'seller_id' => $seller->id, 'type' => $type === 'simple' ? Order::TYPE_CASH : Order::TYPE_FOR_CLIENT,
            'order_type' => $type, 'barcode' => uniqid('SALE-'), 'curr_type' => $currency, 'curr_rate' => 1,
            'tax_ratio' => 5, 'total_price' => $gross, 'total_price_before_discount' => $gross,
            'price_without_tax' => $net, 'tax_value' => round($gross - $net, $decimals),
            'paid_price' => $gross, 'remain_price' => 0, 'payment_type' => 0,
            'first_name' => 'Invoice', 'last_name' => 'Customer',
        ]);
        $category = Category::query()->create(['name' => 'Invoice tests']);
        $color = Color::query()->create(['name' => 'Black', 'code' => '#000000']);
        $product = Product::query()->create([
            'name' => 'Shirt', 'name_en' => 'Shirt', 'barcode' => '18225-' . $order->id,
            'category_id' => $category->id, 'country_id' => $country, 'cost_price' => 1,
        ]);
        foreach ($lines as $index => [$qty, $unitGross, $unitNet]) {
            $variant = ProductColor::query()->create([
                'product_id' => $product->id, 'color_id' => $color->id, 'country_id' => $country,
                'barcode' => uniqid('COLOR-'), 'sizes' => '[]', 'stock' => 0,
            ]);
            $stock = UserProduct::query()->create([
                'product_color_id' => $variant->id, 'user_id' => $seller->id, 'country_id' => $country,
                'size' => 'M', 'stock' => 0, 'barcode' => uniqid('STOCK-'), 'wholesale_price' => 1, 'retail_price' => $unitGross,
            ]);
            $order->items()->create([
                'user_product_id' => $stock->id, 'qty' => $qty, 'sold_qty' => $qty, 'unit_cost' => 1,
                'item_price' => $unitGross, 'price_without_tax' => $unitNet, 'tax_value' => $unitGross - $unitNet,
                'tax_ratio' => 5, 'total_price' => $unitGross * $qty,
                'item_price_paid' => $unitGross, 'price_without_tax_paid' => $unitNet,
                'tax_value_paid' => $unitGross - $unitNet, 'total_price_paid' => $unitGross * $qty,
            ]);
        }
        return $order;
    }
}
