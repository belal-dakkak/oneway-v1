<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Color;
use App\Models\CountryCommerceSetting;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\User;
use App\Models\UserProduct;
use App\Models\WebsiteOrder;
use App\Repositories\OrderRepository;
use App\Services\Payment\WebsiteOrderStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebsiteManualFulfilmentTest extends TestCase
{
    use RefreshDatabase;

    private function stock(): UserProduct
    {
        config(['services.tap.secret_key' => 'sk_test_fake', 'services.tap.callback_url' => 'https://example.test/payment/callback',
            'services.tap.webhook_url' => 'https://example.test/payment/webhook']);
        $seller = User::query()->create(['name' => 'Shop', 'email' => 'manual@example.test', 'password' => 'x',
            'role_id' => User::ROLE_SHOP, 'country_id' => 2, 'enable_tax' => 'no']);
        CountryCommerceSetting::query()->updateOrCreate(['country_id' => 2], [
            'website_stock_user_id' => $seller->id, 'website_cashbox_user_id' => $seller->id,
            'card_enabled' => true, 'gateway_mode' => 'sandbox', 'gateway_currency' => 'AED',
        ]);
        DB::table('currencies')->updateOrInsert(['name' => 'aed'], ['label' => 'AED', 'rate' => 3.67]);
        $category = Category::query()->create(['name' => 'Manual tests']);
        $color = Color::query()->create(['name' => 'Black', 'code' => '#000000']);
        $product = Product::query()->create(['name' => 'Shirt', 'barcode' => 'MANUAL-1', 'country_id' => 2,
            'category_id' => $category->id, 'cost_price' => 1, 'retail_price' => 15, 'sale_price' => 10]);
        $variant = ProductColor::query()->create(['product_id' => $product->id, 'color_id' => $color->id,
            'country_id' => 2, 'barcode' => 'VARIANT-1', 'sizes' => '[]', 'stock' => 10]);
        return UserProduct::query()->create(['product_color_id' => $variant->id, 'user_id' => $seller->id,
            'country_id' => 2, 'size' => 'M', 'stock' => 10, 'barcode' => 'STOCK-1', 'wholesale_price' => 1, 'retail_price' => 55]);
    }

    public function test_cod_and_card_requests_do_not_reserve_stock_and_manual_sale_deducts_once(): void
    {
        $stock = $this->stock();
        $repository = app(OrderRepository::class);
        foreach (['cod', 'card'] as $payment) {
            $website = $repository->addForOnline(new Request([
                'items' => [['product_id' => $stock->product_color_id, 'size' => 'M', 'qty' => 2]],
                'payment' => ['name' => $payment],
            ]));
            $this->assertSame(10, $stock->fresh()->stock);
            $this->assertNull($website->stock_reserved_at);
            $this->assertFalse(app(WebsiteOrderStockService::class)->release($website));
            $this->assertSame(10, $stock->fresh()->stock);
        }
        $this->assertDatabaseCount('wallet_movements', 0);

        $this->actingAs($stock->user);
        $manualOrder = $repository->add(new Request([
            'order_type' => 'simple', 'type' => Order::TYPE_CASH, 'payment' => ['value' => 0],
            'currency' => ['value' => 'AED', 'code' => 'AED', 'rate' => 3.67],
            'selected_products' => [['product_id' => $stock->id, 'qty' => 2, 'price' => 55]],
            'total_price_before_discount' => 110, 'paid_price' => 110,
        ]));
        $this->assertSame(8, $stock->fresh()->stock);
        $this->assertDatabaseHas('wallet_movements', [
            'source_type' => Order::class,
            'source_id' => $manualOrder->id,
            'direction' => 'credit',
            'currency_code' => 'AED',
            'amount' => 110,
        ]);
        $this->assertDatabaseCount('wallet_movements', 1);
    }

    public function test_unavailable_quantity_is_still_rejected(): void
    {
        $stock = $this->stock();
        try {
            app(OrderRepository::class)->addForOnline(new Request([
                'items' => [['product_id' => $stock->product_color_id, 'size' => 'M', 'qty' => 11]],
                'payment' => ['name' => 'cod'],
            ]));
            $this->fail('Unavailable stock must be rejected.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('quantity', $exception->getMessage());
        }
        $this->assertSame(10, $stock->fresh()->stock);
        $this->assertSame(0, WebsiteOrder::count());
    }

    public function test_checkout_redirects_to_gateway_without_stock_deduction_and_handles_rejection(): void
    {
        $stock = $this->stock();
        $payload = ['items' => [['color' => ['id' => $stock->product_color_id], 'quantity' => 1, 'size' => 'M']],
            'first_name' => 'Test', 'last_name' => 'Buyer', 'phone' => '+971501234567', 'email' => 'checkout@example.test',
            'address' => 'Ajman', 'city' => 'Ajman', 'building_name' => '1', 'flat_number' => '1', 'payment_method' => 'card'];
        Http::fake(['api.tap.company/*' => Http::sequence()
            ->push(['id' => 'chg_mock', 'transaction' => ['url' => 'https://example.test/pay']], 200)
            ->push(['errors' => [['code' => 'bad', 'description' => 'internal']]], 400)]);
        $this->withSession(['country' => 'AE'])->withHeader('X-Inertia', 'true')->post('/checkout', $payload)
            ->assertStatus(409)->assertHeader('X-Inertia-Location', 'https://example.test/pay');
        $this->assertSame(10, $stock->fresh()->stock);
        $this->assertSame('chg_mock', WebsiteOrder::first()->invoice);
        Http::assertSent(fn ($request) => $request['customer']['phone']['number'] === '501234567'
            && $request['currency'] === 'AED' && $request['metadata']['order_id'] === WebsiteOrder::first()->id);
        $this->post('/checkout', $payload)->assertSessionHasErrors('payment');
        $this->assertSame(10, $stock->fresh()->stock);
        $this->assertSame(WebsiteOrder::STATUS_FAILED, WebsiteOrder::latest('id')->first()->status);
    }
}
