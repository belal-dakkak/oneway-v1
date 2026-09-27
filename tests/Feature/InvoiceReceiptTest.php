<?php

namespace Tests\Feature;

use App\Models\CountryCommerceSetting;
use App\Models\Order;
use App\Models\User;
use App\Models\WebsiteOrder;
use App\Support\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_and_typed_print_routes_default_to_receipts_and_keep_a4_explicit(): void
    {
        $seller = User::query()->create([
            'name' => 'Legal receipt seller', 'email' => 'receipt@example.test', 'password' => 'secret',
            'country_id' => Country::UAE, 'role_id' => User::ROLE_SHOP,
            'enable_tax' => 'yes', 'trn' => '1048430610003', 'tax_ratio' => 5,
        ]);
        CountryCommerceSetting::query()->updateOrCreate(['country_id' => Country::UAE], [
            'website_stock_user_id' => $seller->id,
        ]);
        $fields = [
            'barcode' => '9789630163', 'curr_type' => 'AED', 'curr_rate' => 1,
            'total_price' => 100, 'paid_price' => 100, 'remain_price' => 0,
            'first_name' => 'Stored', 'last_name' => 'Customer',
        ];
        $order = Order::query()->create($fields + [
            'seller_id' => $seller->id, 'type' => Order::TYPE_CASH, 'payment_type' => 0,
            'price_without_tax' => 95.24, 'tax_value' => 4.76, 'tax_ratio' => 5,
        ]);
        $website = WebsiteOrder::query()->create(array_merge($fields, [
            'country_id' => Country::UAE, 'payment_type' => 'cod', 'first_name' => 'Website',
        ]));
        $this->actingAs($seller);
        foreach ([
            ['invoice.printv2', ['id' => $order->id], 'Stored Customer'],
            ['invoice.typed.printv2', ['source' => 'order', 'id' => $order->id], 'Stored Customer'],
            ['invoice.typed.printv2', ['source' => 'website', 'id' => $website->id], 'Website Customer'],
        ] as [$route, $parameters, $buyer]) {
            foreach ([[], ['format' => 'receipt']] as $format) {
                $response = $this->get(route($route, $parameters + $format))->assertOk()
                    ->assertViewIs('includes.printer')->assertSee('id="bodyContent"', false)
                    ->assertSee($buyer)->assertSee('Legal receipt seller')->assertSee('1048430610003')
                    ->assertSee('Branch 1 : UAE – Ajman')->assertSee('9789630163')
                    ->assertSee('Exchange Policy')->assertDontSee('BILL TO')
                    ->assertSee('document.fonts.ready', false)->assertSee('img.decode()', false);
                if (($parameters['source'] ?? '') === 'website') $response->assertSee('Cash on delivery');
            }
            $this->get(route($route, $parameters + ['format' => 'a4']))->assertOk()
                ->assertViewIs('includes.invoice_template')->assertSee('BILL TO')->assertSee($buyer);
        }
        $this->get(route('invoice.typed.show', ['source' => 'order', 'id' => $order->id]))
            ->assertOk()->assertViewIs('includes.invoice_template')->assertSee('BILL TO');
        $this->get(route('download.invoice.typed', ['source' => 'order', 'id' => $order->id]))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->app['auth']->forgetGuards();
        $this->get(route('invoice.printv2', ['id' => $order->id]))->assertForbidden();
    }

    public function test_receipts_preserve_amounts_and_render_approved_copy_without_extra_table_columns(): void
    {
        foreach ([Country::UAE, Country::SYRIA] as $country) {
            foreach ([false, true] as $taxable) {
                foreach ([1, 9, 50] as $count) {
                    $syrian = $country === Country::SYRIA;
                    $currency = $syrian ? 'SYP' : 'AED';
                    $unit = $syrian ? 3250000 : 100;
                    $net = $taxable ? ($syrian ? 3095238 : 95.24) : $unit;
                    $seller = new User(['name' => 'ONE WAY CLOTHING TRADING L.L.C (SOLE PROPRIETORSHIP)', 'country_id' => $country, 'tax_ratio' => 5]);
                    $order = new Order([
                        'barcode' => '9789630163', 'curr_type' => $currency, 'payment_type' => 2,
                        'first_name' => 'عميل طويل الاسم', 'last_name' => 'Long customer name',
                        'total_price' => $unit * $count + 10, 'price_without_tax' => $net * $count,
                        'tax_value' => ($unit - $net) * $count, 'shipping_fee' => 10,
                        'paid_price' => 0, 'remain_price' => $unit * $count + 10,
                    ]);
                    $order->id = 19660;
                    $order->created_at = '2026-09-27 14:52:00';
                    $order->setRelation('seller', $seller);
                    $items = collect(range(1, $count))->map(fn ($i) => (object) [
                        'name' => $i % 3 === 0 ? 'طقم نسائي بأكمام طويلة وتصميم مميز - Long garment name - ITEM-' . $i : 'Style Set - ITEM-' . $i,
                        'qty' => 1, 'item_price' => $unit, 'line_price_without_tax' => $net,
                        'line_tax_value' => $unit - $net, 'total_price' => $unit,
                    ]);
                    $html = view('includes.printer', [
                        'order' => $order, 'items' => $items, 'Currency' => $currency,
                        'invoiceCountryId' => $country,
                        'invoiceIdentity' => ['name' => $seller->name, 'tax_enabled' => $taxable, 'trn' => '1048430610003'],
                    ])->render();
                    $this->assertStringContainsString($syrian ? 'الفرع الأول: الإمارات – عجمان' : 'Branch 1 : UAE – Ajman', $html);
                    $this->assertLessThan(strpos($html, '+963 947 900 555'), strpos($html, '+963 958 900 555'));
                    $this->assertStringNotContainsString('Ajman Industrial', $html);
                    $this->assertStringNotContainsString('Sharjah City', $html);
                    $this->assertStringNotContainsString('غير مطابقة للمواصفات', $html);
                    $this->assertStringContainsString('theoneway.fashion@gmail.com', $html);
                    $this->assertSame(8, substr_count($html, '<li>'));
                    $this->assertStringContainsString('خلال مدة أقصاها 5 أيام', $html);
                    $this->assertStringContainsString('within a maximum of 5 days', $html);
                    $this->assertSame($taxable, str_contains($html, 'TAX INVOICE'));
                    $this->assertStringContainsString(number_format($order->total_price, $syrian ? 0 : 2) . ' ' . $currency, $html);
                    $this->assertStringContainsString('Pay by Cheque', $html);
                    $this->assertDoesNotMatchRegularExpression('/[\x{0660}-\x{0669}\x{FFFD}]|ط§|ظ„/u', $html);
                    $dom = new \DOMDocument();
                    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
                    $xpath = new \DOMXPath($dom);
                    foreach ($xpath->query('//table[@class="items"]//tr') as $row) {
                        $columns = 0;
                        foreach ($xpath->query('./td|./th', $row) as $cell) $columns += (int) ($cell->getAttribute('colspan') ?: 1);
                        $this->assertSame($taxable ? 6 : 4, $columns);
                    }
                    $this->assertSame($count, $xpath->query('//tr[@class="item-row"]')->length);
                    if ($directory = getenv('RECEIPT_OUTPUT_DIR')) {
                        file_put_contents($directory . '/' . ($syrian ? 'SY' : 'AE') . '-' . ($taxable ? 'TAX' : 'NORMAL') . '-' . $count . '.html', $html);
                    }
                }
            }
        }
    }
}
