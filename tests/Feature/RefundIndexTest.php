<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Color;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\Refund;
use App\Models\User;
use App\Models\UserProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RefundIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_refund_tab_opens_in_both_countries(): void
    {
        Currency::query()->updateOrCreate(['name' => 'aed'], ['label' => 'AED', 'rate' => 3.67]);
        foreach ([User::COUNTRY_SYRIA, User::COUNTRY_UAE] as $country) {
            $admin = $this->user(User::ROLE_ADMIN, $country, 'empty-' . $country . '@example.test');
            $this->actingAs($admin)->get(route('refunds.index'))->assertOk();
            $this->actingAs($admin)->getJson(route('refunds.index'))
                ->assertOk()->assertJsonPath('rows.total', 0)->assertJsonPath('totals_by_currency', []);
        }
    }

    public function test_refund_totals_keep_currencies_separate_with_filters_and_pagination(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, User::COUNTRY_SYRIA, 'refund-admin@example.test');
        $shop = $this->user(User::ROLE_SHOP, User::COUNTRY_SYRIA, 'refund-shop@example.test');
        $otherShop = $this->user(User::ROLE_SHOP, User::COUNTRY_UAE, 'refund-other@example.test');
        $category = Category::query()->create(['name' => 'Refund test']);
        $color = Color::query()->create(['name' => 'Black', 'code' => '#000000']);
        $product = Product::query()->create(['name' => 'Refund product', 'barcode' => 'RF-P', 'country_id' => User::COUNTRY_SYRIA, 'category_id' => $category->id, 'cost_price' => 1]);
        $variant = ProductColor::query()->create(['product_id' => $product->id, 'color_id' => $color->id, 'country_id' => User::COUNTRY_SYRIA, 'barcode' => 'RF-C', 'sizes' => '[]', 'stock' => 0]);
        $stock = UserProduct::query()->create(['product_color_id' => $variant->id, 'user_id' => $shop->id, 'country_id' => User::COUNTRY_SYRIA, 'size' => 'M', 'barcode' => 'RF-M', 'stock' => 0, 'wholesale_price' => 1, 'retail_price' => 2]);

        $syp = $this->item($shop, $stock, 'RF-SYP', 'SYP');
        $usd = $this->item($shop, $stock, 'RF-USD', 'USD');
        $outside = $this->item($otherShop, $stock, 'RF-OUT', 'AED');
        foreach (range(1, 11) as $number) {
            $this->refund($syp, "RF-SYP-{$number}", 'SYP', 100);
        }
        $this->refund($usd, 'RF-USD-1', 'USD', 2);
        $this->refund($outside, 'RF-OUT-1', 'AED', 9);

        $this->actingAs($admin)->get(route('refunds.index'))->assertOk();
        $response = $this->actingAs($admin)->getJson(route('refunds.index'))
            ->assertOk()->assertJsonPath('rows.total', 12)
            ->assertJsonPath('refunds.total', 12)
            ->assertJsonPath('totals_by_currency.SYP', 1100)
            ->assertJsonPath('totals_by_currency.USD', 2);
        $this->assertCount(10, $response->json('rows.data'));

        $this->actingAs($admin)->getJson(route('refunds.index', ['page' => 2]))
            ->assertOk()->assertJsonCount(2, 'rows.data');
        $this->actingAs($admin)->getJson(route('refunds.index', ['search' => 'RF-USD-1']))
            ->assertOk()->assertJsonPath('rows.total', 1)
            ->assertJsonPath('totals_by_currency.USD', 2);
        $this->actingAs($admin)->getJson(route('refunds.index', ['start_date' => '2030-01-01']))
            ->assertOk()->assertJsonPath('rows.total', 0);
    }

    public function test_totals_query_is_accepted_by_mysql_only_full_group_by(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Run this test against the production MySQL schema in CI.');
        }

        $previousMode = DB::selectOne('SELECT @@SESSION.sql_mode AS mode')->mode;
        try {
            DB::statement("SET SESSION sql_mode = CONCAT(@@sql_mode, ',ONLY_FULL_GROUP_BY')");
            $admin = $this->user(User::ROLE_ADMIN, User::COUNTRY_SYRIA, 'strict-mysql@example.test');
            $this->actingAs($admin)->getJson(route('refunds.index'))
                ->assertOk()->assertJsonPath('totals_by_currency', []);
        } finally {
            DB::statement('SET SESSION sql_mode = ?', [$previousMode]);
        }
    }

    private function user(int $role, int $country, string $email): User
    {
        return User::query()->create(['name' => $email, 'email' => $email, 'password' => 'secret', 'role_id' => $role, 'country_id' => $country]);
    }

    private function item(User $seller, UserProduct $stock, string $barcode, string $currency): OrderItem
    {
        $order = Order::query()->create(['seller_id' => $seller->id, 'barcode' => $barcode, 'type' => Order::TYPE_CASH, 'curr_type' => $currency, 'curr_rate' => 1, 'total_price' => 2, 'paid_price' => 2, 'remain_price' => 0]);
        return OrderItem::query()->create(['order_id' => $order->id, 'user_product_id' => $stock->id, 'qty' => 1, 'item_price' => 2, 'total_price' => 2]);
    }

    private function refund(OrderItem $item, string $barcode, string $currency, float $amount): void
    {
        Refund::query()->create(['order_item_id' => $item->id, 'qty' => 1, 'item_price' => $amount, 'total_price' => $amount, 'total_price_paid' => $amount, 'currency_code' => $currency, 'item_barcode' => $barcode, 'order_barcode' => $barcode]);
    }
}
