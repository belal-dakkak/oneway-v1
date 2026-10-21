<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\User;
use App\Models\WebsiteOrder;
use App\Models\WebsiteOrderItem;
use App\Repositories\OrderRepository;
use App\Support\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class WebsiteOrderListSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_website_order_list_contains_a_compact_product_and_customer_summary(): void
    {
        $admin = User::factory()->create([
            'role_id' => User::ROLE_ADMIN,
            'country_id' => Country::UAE,
        ]);
        $this->actingAs($admin);

        $category = Category::query()->create(['name' => 'Sets']);
        $color = Color::query()->create(['name' => 'أسود', 'name_en' => 'Black', 'code' => '#000000']);
        $product = Product::query()->create([
            'name' => 'طقم', 'name_en' => 'Set', 'details' => 'Details', 'details_en' => 'Details',
            'barcode' => 'MODEL-SUMMARY', 'category_id' => $category->id,
            'country_id' => Country::UAE, 'cost_price' => 10, 'retail_price' => 20,
        ]);
        $productColor = ProductColor::query()->create([
            'product_id' => $product->id, 'color_id' => $color->id,
            'image' => 'products/black.jpg', 'barcode' => 'MODEL-SUMMARY-B',
            'sizes' => '[]', 'stock' => 0, 'country_id' => Country::UAE,
        ]);
        $order = WebsiteOrder::query()->create([
            'barcode' => 'WEB-SUMMARY', 'first_name' => 'Bayan', 'last_name' => 'Test',
            'email' => 'buyer@example.test', 'phone' => '+971500000000',
            'total_price' => 50, 'paid_price' => 0, 'remain_price' => 50,
            'curr_type' => 'AED', 'country_id' => Country::UAE, 'payment_type' => 'cod',
        ]);
        WebsiteOrderItem::query()->create([
            'website_order_id' => $order->id, 'product_color_id' => $productColor->id,
            'size' => 'M', 'qty' => 2, 'item_price' => 25, 'total_price' => 50,
        ]);

        $result = app(OrderRepository::class)->getWebsiteOrders(new Request(), true);
        $row = $result['orders']->items()[0];

        $this->assertSame('Bayan', $row['first_name']);
        $this->assertSame('+971500000000', $row['phone']);
        $this->assertSame('طقم', $row['product_summary'][0]['name']);
        $this->assertSame('M', $row['product_summary'][0]['size']);
        $this->assertSame(2, $row['product_summary'][0]['qty']);
        $this->assertArrayNotHasKey('items', $row);
        $this->assertLessThan(20000, strlen(json_encode($row, JSON_UNESCAPED_UNICODE)));
    }
}
