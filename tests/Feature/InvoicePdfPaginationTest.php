<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CountryCommerceSetting;
use App\Models\Color;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\User;
use App\Models\UserProduct;
use App\Models\WebsiteOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePdfPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_website_pdfs_keep_country_identity_and_allow_long_notes_to_flow_without_shrinking(): void
    {
        foreach ([User::COUNTRY_UAE, User::COUNTRY_SYRIA] as $country) {
            foreach ([false, true] as $taxable) {
                $source = $this->orderWithItems($country, $taxable, 9);
                CountryCommerceSetting::query()->updateOrCreate(['country_id' => $country], [
                    'website_stock_user_id' => $source->seller_id,
                    'website_cashbox_user_id' => $source->seller_id,
                ]);
                $order = WebsiteOrder::query()->create([
                    'country_id' => $country, 'barcode' => 'WEB-' . $source->barcode,
                    'curr_type' => $source->curr_type, 'curr_rate' => 1, 'payment_type' => 'card',
                    'total_price' => $source->total_price, 'paid_price' => $source->total_price, 'remain_price' => 0,
                    'first_name' => 'عميل الاسم الطويل', 'last_name' => 'Long customer name',
                    'address' => 'Main shopping street, building 25, floor 3, Aleppo / Sharjah',
                    'phone' => '+963 900 111 222',
                ]);
                foreach ($source->items as $item) {
                    $order->items()->create([
                        'product_color_id' => $item->user_product->product_color_id, 'size' => 'M', 'qty' => 1,
                        'item_price' => $item->item_price, 'total_price' => $item->total_price,
                    ]);
                }
                foreach ([false, true] as $longNotes) {
                    $order->update(['notes' => $longNotes
                        ? implode("\n", array_map(fn ($i) => sprintf('NOTE-%03d ملاحظات إضافية للطلب يرجى الاحتفاظ بها كاملة دون اقتطاع.', $i), range(1, 80)))
                        : 'ملاحظات العميل الإضافية']);
                    $this->actingAs($source->seller);
                    $preview = $this->get(route('invoice.typed.show', ['source' => 'website', 'id' => $order->id]))->assertOk();
                    $preview->assertSee($source->seller->name)->assertDontSee('بعد 90');
                    if ($taxable) {
                        $preview->assertSee($source->seller->trn);
                    }
                    $response = $this->get(route('download.invoice.typed', ['source' => 'website', 'id' => $order->id]))
                        ->assertOk()->assertHeader('content-type', 'application/pdf');
                    $pdf = $response->getContent();
                    if ($directory = getenv('INVOICE_PDF_OUTPUT_DIR')) {
                        file_put_contents($directory . DIRECTORY_SEPARATOR . $order->barcode . ($longNotes ? '-NOTES' : '') . '.pdf', $pdf);
                        file_put_contents($directory . DIRECTORY_SEPARATOR . $order->barcode . ($longNotes ? '-NOTES' : '') . '.html', $preview->getContent());
                    }
                    $pageCount = preg_match_all('/\/Type \/Page\s/', $pdf);
                    if ($longNotes) {
                        $preview->assertSee('NOTE-001')->assertSee('NOTE-080');
                        $this->assertGreaterThan(1, $pageCount);
                        $this->assertLessThanOrEqual(4, $pageCount);
                    } else {
                        $this->assertSame(1, $pageCount, $order->barcode);
                    }
                }
            }
        }
    }

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
                    if ($directory = getenv('INVOICE_PDF_OUTPUT_DIR')) {
                        file_put_contents($directory . DIRECTORY_SEPARATOR . $order->barcode . '.pdf', $pdf);
                        $preview = $this->get(route('invoice.typed.show', ['source' => 'order', 'id' => $order->id]))->assertOk();
                        file_put_contents($directory . DIRECTORY_SEPARATOR . $order->barcode . '.html', $preview->getContent());
                    }
                    $this->assertStringStartsWith('%PDF-', $pdf);
                    $this->assertGreaterThan(2000, strlen($pdf));
                    $pageCount = preg_match_all('/\/Type \/Page\s/', $pdf);
                    if ($count === 9) {
                        $this->assertSame(1, $pageCount, $order->barcode);
                    } else {
                        $this->assertGreaterThanOrEqual(2, $pageCount);
                        $this->assertLessThanOrEqual(3, $pageCount);
                    }
                }
            }
        }
    }

    private function orderWithItems(int $country, bool $taxable, int $count): Order
    {
        $currency = $country === User::COUNTRY_UAE ? 'AED' : ($country === User::COUNTRY_SYRIA ? 'SYP' : 'USD');
        $unit = $currency === 'SYP' ? 3250000 : 25;
        $net = $taxable ? round($unit / 1.05, $currency === 'SYP' ? 0 : 2) : $unit;
        $tax = $unit - $net;
        $countryCode = $country === User::COUNTRY_UAE ? 'AE' : ($country === User::COUNTRY_SYRIA ? 'SY' : 'LB');
        $code = $countryCode . '-' . ($taxable ? 'TAX' : 'NORMAL') . '-' . $count;
        $seller = User::query()->create([
            'name' => 'ONE WAY CLOTHING TRADING LLC SOLE PROPRIETORSHIP', 'email' => strtolower($code) . '@example.test',
            'phone' => '+971 500 000 001', 'address' => 'Main branch', 'password' => 'secret',
            'role_id' => User::ROLE_SHOP, 'country_id' => $country,
            'enable_tax' => $taxable ? 'yes' : 'no', 'tax_ratio' => $taxable ? 5 : 0,
            'trn' => $taxable ? '123456789012345' : null,
        ]);
        $order = Order::query()->create([
            'seller_id' => $seller->id, 'barcode' => $code, 'type' => Order::TYPE_CASH,
            'curr_type' => $currency, 'curr_rate' => 1, 'total_price' => $count * $unit,
            'paid_price' => 0, 'remain_price' => $count * $unit, 'payment_type' => 2,
            'tax_ratio' => $taxable ? 5 : 0,
            'tax_value' => $count * $tax,
            'price_without_tax' => $count * $net,
            'first_name' => 'Crystal', 'last_name' => 'Gift', 'phone' => '+971 500 000 002',
            'address' => 'Sharjah',
        ]);
        $category = Category::query()->create(['name' => 'PDF ' . $code]);
        $color = Color::query()->create(['name' => 'Black', 'code' => '#000000']);
        for ($number = 1; $number <= $count; $number++) {
            $product = Product::query()->create([
                'name' => $count > 9 && $number % 5 === 0 ? 'طقم نسائي بأكمام طويلة وتصميم مميز للمناسبات مع تفاصيل مطرزة' : 'طقم نسائي أنيق',
                'name_en' => $count > 9 && $number % 5 === 0 ? 'Elegant long sleeve embroidered occasion garment with matching accessories' : 'Elegant set',
                'barcode' => $country . (int) $taxable . $count . 'Y' . $number,
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
                'stock' => 0, 'wholesale_price' => 10, 'retail_price' => $unit,
            ]);
            $order->items()->create([
                'user_product_id' => $stock->id, 'qty' => 1, 'sold_qty' => 1,
                'item_price' => $unit, 'total_price' => $unit,
                'price_without_tax' => $net,
                'tax_value' => $tax,
            ]);
        }

        return $order;
    }
}
