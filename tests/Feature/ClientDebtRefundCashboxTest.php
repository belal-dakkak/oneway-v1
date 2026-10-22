<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ClientDebit;
use App\Models\Color;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\User;
use App\Models\UserProduct;
use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClientDebtRefundCashboxTest extends TestCase
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

    public function test_customer_debt_refund_reduces_debt_and_sale_without_touching_cashbox(): void
    {
        [$seller, $buyer, $stock, $order, $item, $account] = $this->saleFixture(270, 270, 3);

        $this->actingAs($seller)->postJson(route('merchantRefunds.client'), [
            'client_id' => $buyer->id,
            'user_products' => json_encode([['order_item_id' => $item->id, 'qty' => 1]]),
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertEquals(180, $account->fresh()->amount);
        $this->assertEquals(180, $order->fresh()->total_price);
        $this->assertEquals(180, $order->fresh()->remain_price);
        $this->assertSame(2, $item->fresh()->qty);
        $this->assertSame(1, $stock->fresh()->stock);
        $this->assertDatabaseHas('client_debit_logs', [
            'client_debit_id' => $account->id,
            'amount' => -90,
            'currency_code' => 'AED',
        ]);
        $this->assertDatabaseCount('wallet_movements', 0);
    }

    public function test_customer_debt_refund_can_create_credit_without_touching_cashbox(): void
    {
        [$seller, $buyer, $stock, $order, $item, $account] = $this->saleFixture(90, 40, 1);

        $this->actingAs($seller)->postJson(route('merchantRefunds.client'), [
            'client_id' => $buyer->id,
            'user_products' => json_encode([['order_item_id' => $item->id, 'qty' => 1]]),
        ])->assertOk();

        $this->assertEquals(-50, $account->fresh()->amount);
        $this->assertEquals(0, $order->fresh()->total_price);
        $this->assertEquals(0, $order->fresh()->remain_price);
        $this->assertSame(1, $stock->fresh()->stock);
        $this->assertDatabaseCount('wallet_movements', 0);
    }

    public function test_customer_credit_after_refund_closes_other_matching_historical_orders_without_cash(): void
    {
        [$seller, $buyer, $stock, $order, $item, $account] = $this->saleFixture(90, 40, 1);
        $historical = Order::query()->create([
            'seller_id' => $seller->id,
            'buyer_id' => $buyer->id,
            'type' => Order::TYPE_FOR_CLIENT,
            'barcode' => uniqid('historical-', true),
            'curr_type' => 'AED',
            'curr_rate' => 3.675,
            'total_price' => 25,
            'paid_price' => 0,
            'remain_price' => 25,
        ]);

        $this->actingAs($seller)->postJson(route('merchantRefunds.client'), [
            'client_id' => $buyer->id,
            'user_products' => json_encode([['order_item_id' => $item->id, 'qty' => 1]]),
        ])->assertOk();

        $this->assertEquals(-50, $account->fresh()->amount);
        $this->assertEquals(0, $order->fresh()->remain_price);
        $this->assertEquals(0, $historical->fresh()->remain_price);
        $this->assertEquals(25, $historical->fresh()->paid_price);
        $this->assertDatabaseHas('client_debt_reconciliations', [
            'client_debit_id' => $account->id,
            'order_id' => $historical->id,
            'reason' => 'account_settled_after_customer_refund',
        ]);
        $this->assertDatabaseCount('wallet_movements', 0);
        $this->assertDatabaseCount('order_payments', 0);
    }

    public function test_multi_item_refund_rolls_back_when_any_quantity_is_invalid(): void
    {
        [$seller, $buyer, $stock, $order, $item, $account] = $this->saleFixture(270, 270, 3);

        $this->actingAs($seller)->postJson(route('merchantRefunds.client'), [
            'client_id' => $buyer->id,
            'user_products' => json_encode([
                ['order_item_id' => $item->id, 'qty' => 1],
                ['order_item_id' => $item->id, 'qty' => 99],
            ]),
        ])->assertStatus(422);

        $this->assertEquals(270, $account->fresh()->amount);
        $this->assertEquals(270, $order->fresh()->total_price);
        $this->assertSame(3, $item->fresh()->qty);
        $this->assertSame(0, $stock->fresh()->stock);
        $this->assertDatabaseCount('refunds', 0);
        $this->assertDatabaseCount('client_refunds', 0);
        $this->assertDatabaseCount('wallet_movements', 0);
    }

    private function saleFixture(float $total, float $debt, int $qty): array
    {
        $seller = User::factory()->create([
            'role_id' => User::ROLE_SHOP,
            'country_id' => User::COUNTRY_UAE,
        ]);
        $buyer = User::factory()->create([
            'role_id' => User::ROLE_CLIENT,
            'country_id' => User::COUNTRY_UAE,
        ]);
        $category = Category::query()->create(['name' => 'Jackets']);
        $color = Color::query()->create(['name' => 'Black', 'name_en' => 'Black', 'code' => '#000000']);
        $product = Product::query()->create([
            'name' => 'Jacket',
            'category_id' => $category->id,
            'barcode' => uniqid('product-', true),
            'cost_price' => 20,
            'retail_price' => 24.4898,
            'sale_price' => 20,
            'country_id' => User::COUNTRY_UAE,
        ]);
        $productColor = ProductColor::query()->create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'barcode' => uniqid('color-', true),
            'country_id' => User::COUNTRY_UAE,
            'sizes' => json_encode([['size' => 'M', 'stock' => 0]]),
        ]);
        $stock = UserProduct::query()->create([
            'product_color_id' => $productColor->id,
            'user_id' => $seller->id,
            'country_id' => User::COUNTRY_UAE,
            'stock' => 0,
            'size' => 'M',
            'barcode' => uniqid('stock-', true),
        ]);
        $order = Order::query()->create([
            'seller_id' => $seller->id,
            'buyer_id' => $buyer->id,
            'type' => Order::TYPE_FOR_CLIENT,
            'barcode' => uniqid('order-', true),
            'curr_type' => 'AED',
            'curr_rate' => 3.675,
            'total_price' => $total,
            'paid_price' => max(0, $total - $debt),
            'remain_price' => $debt,
            'tax_value' => 0,
            'price_without_tax' => $total,
        ]);
        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'user_product_id' => $stock->id,
            'qty' => $qty,
            'sold_qty' => $qty,
            'item_price' => 24.4898,
            'item_price_paid' => 90,
            'total_price' => 24.4898 * $qty,
            'total_price_paid' => 90 * $qty,
            'price_without_tax_paid' => 90,
        ]);
        $account = ClientDebit::query()->create([
            'creditor_id' => $seller->id,
            'debtor_id' => $buyer->id,
            'amount' => $debt,
            'currency_code' => 'AED',
        ]);

        return [$seller, $buyer, $stock, $order, $item, $account];
    }
}
