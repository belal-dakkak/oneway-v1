<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Color;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\User;
use App\Models\UserProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePdfPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_download_button_route_renders_short_and_long_invoices_for_all_supported_countries(): void
    {
        foreach ([User::COUNTRY_LB, User::COUNTRY_UAE, User::COUNTRY_SYRIA] as $country) {
            foreach ([false, true] as $taxable) {
                foreach ([9, 26, 50] as $count) {
                    $order = $this->orderWithItems($country, $taxable, $count);
                    $admin = User::query()->create([
                        'name' => 'Invoice admin', 'email' => uniqid('pdf-admin-', true) . '@example.test',
                        'password' => 'secret', 'role_id' => User::ROLE_ADMIN, 'country_id' => $country,
                    ]);
                    $response = $this->actingAs($admin)->get(route('download.invoice.typed', [
                        'source' => 'order', 'id' => $order->id,
                    ]));
                    $response->assertOk();
                    $response->assertHeader('content-type', 'application/pdf');
                    $response->assertHeader('content-disposition', 'attachment; filename="Invoice_Order_' . $order->id . '.pdf"');
                    $pdf = $response->getContent();
                    $this->assertStringStartsWith('%PDF-', $pdf);
                    $this->assertGreaterThan(2000, strlen($pdf));
                    $pageCount = preg_match_all('/\/Type \/Page\s/', $pdf);
                    if ($count === 9) {
                        $this->assertSame(1, $pageCount);
                    } else {
                        $this->assertGreaterThanOrEqual(2, $pageCount);
                        $this->assertLessThanOrEqual(3, $pageCount);
                    }
                    if ($directory = getenv('INVOICE_PDF_OUTPUT_DIR')) {
                        file_put_contents($directory . DIRECTORY_SEPARATOR . $order->barcode . '.pdf', $pdf);
                    }
                }
            }
        }
    }

    private function orderWithItems(int $country, bool $taxable, int $count): Order
    {
        $currency = $country === User::COUNTRY_UAE ? 'AED' : 'USD';
        $countryCode = $country === User::COUNTRY_UAE ? 'AE' : ($country === User::COUNTRY_SYRIA ? 'SY' : 'LB');
        $code = $countryCode . '-' . ($taxable ? 'TAX' : 'NORMAL') . '-' . $count;
        $seller = User::query()->create([
            'name' => 'One Way ' . $code, 'email' => strtolower($code) . '@example.test',
            'phone' => '+971 500 000 001', 'address' => 'Main branch', 'password' => 'secret',
            'role_id' => User::ROLE_SHOP, 'country_id' => $country,
            'enable_tax' => $taxable ? 'yes' : 'no', 'tax_ratio' => $taxable ? 5 : 0,
            'trn' => $taxable ? '123456789012345' : null,
        ]);
        $order = Order::query()->create([
            'seller_id' => $seller->id, 'barcode' => $code, 'type' => Order::TYPE_CASH,
            'curr_type' => $currency, 'curr_rate' => 1, 'total_price' => $count * 25,
            'paid_price' => $count * 25, 'remain_price' => 0,
            'tax_ratio' => $taxable ? 5 : 0,
            'tax_value' => $taxable ? $count * 1.19 : 0,
            'price_without_tax' => $taxable ? $count * 23.81 : $count * 25,
            'first_name' => 'Crystal', 'last_name' => 'Gift', 'phone' => '+971 500 000 002',
            'address' => 'Sharjah',
        ]);
        $category = Category::query()->create(['name' => 'PDF ' . $code]);
        $color = Color::query()->create(['name' => 'Black', 'code' => '#000000']);
        for ($number = 1; $number <= $count; $number++) {
            $product = Product::query()->create([
                'name' => 'Invoice garment ' . $number, 'barcode' => $code . '-P' . $number,
                'country_id' => $country, 'category_id' => $category->id, 'cost_price' => 10,
            ]);
            $variant = ProductColor::query()->create([
                'product_id' => $product->id, 'color_id' => $color->id,
                'country_id' => $country, 'barcode' => $code . '-C' . $number,
                'sizes' => '[]', 'stock' => 0,
            ]);
            $stock = UserProduct::query()->create([
                'product_color_id' => $variant->id, 'user_id' => $seller->id,
                'country_id' => $country, 'size' => 'M', 'barcode' => $code . '-S' . $number,
                'stock' => 0, 'wholesale_price' => 10, 'retail_price' => 25,
            ]);
            $order->items()->create([
                'user_product_id' => $stock->id, 'qty' => 1, 'sold_qty' => 1,
                'item_price' => 25, 'total_price' => 25,
                'price_without_tax' => $taxable ? 23.81 : 25,
                'tax_value' => $taxable ? 1.19 : 0,
            ]);
        }

        return $order;
    }
}
