<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Models\WebsiteOrder;
use App\Services\InvoiceDataService;
use App\Support\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceCountryPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_country_design_is_independent_of_viewer_country_and_interface_language_on_both_routes(): void
    {
        foreach ([Country::UAE, Country::SYRIA] as $country) {
            $seller = User::query()->create([
                'name' => 'Legal seller ' . $country, 'country_id' => $country,
                'role_id' => User::ROLE_SHOP, 'password' => 'secret',
                'email' => 'country-seller-' . $country . '@example.test',
                'enable_tax' => 'yes', 'trn' => '100123456789003', 'tax_ratio' => 5,
            ]);
            $fields = [
                'barcode' => 'COUNTRY-' . $country, 'curr_type' => 'USD', 'curr_rate' => 1,
                'total_price' => 105, 'paid_price' => 0, 'remain_price' => 105,
                'first_name' => 'Stored customer', 'address' => 'عنوان العميل المخزن – عجمان الصناعية 2 شارع بيروت – Ajman Industrial 2 Beirut Street', 'phone' => '+963900000000',
            ];
            $orders = [
                'order' => Order::query()->create($fields + ['seller_id' => $seller->id, 'type' => Order::TYPE_CASH, 'tax_ratio' => 5, 'tax_value' => 5, 'price_without_tax' => 100]),
                'website' => WebsiteOrder::query()->create($fields + ['country_id' => $country, 'payment_type' => 'cod']),
            ];
            foreach ([$country, Country::ALL] as $viewerCountry) {
                $admin = User::query()->create([
                    'name' => 'Admin', 'country_id' => $viewerCountry, 'role_id' => User::ROLE_ADMIN,
                    'email' => uniqid('viewer-', true) . '@example.test', 'password' => 'secret',
                ]);
                foreach (['ar', 'en'] as $locale) {
                    foreach ($orders as $source => $order) {
                        foreach (['invoice.typed.show', 'invoice.typed.printv2'] as $route) {
                            $response = $this->actingAs($admin)->withSession(['locale' => $locale])
                                ->get(route($route, ['source' => $source, 'id' => $order->id]))->assertOk();
                            $response->assertSee('BILL TO')->assertSee('Name:')->assertSee('Phone:')->assertSee('Address:')
                                ->assertSee('Stored customer')->assertSee($fields['address'])->assertSee('USD');
                            $response->assertDontSee('class="reference"', false)
                                ->assertDontSee('summary-reference')->assertDontSee('title-ar')
                                ->assertSee('color: #111111', false)->assertSee('background: #fac5d2', false)
                                ->assertSee('solid #555555', false)->assertSee('color: #d52040', false);
                            $html = $response->getContent();
                            $body = substr($html, strpos($html, '<body>'));
                            $this->assertSame(1, substr_count($body, $order->barcode), 'Invoice number belongs only in the metadata box.');
                            $this->assertApprovedCopy($html, $country);
                            if ($country === Country::SYRIA) {
                                $response->assertSeeInOrder(['class="customer"', 'class="brand"', 'class="directory"', 'class="customer customer-meta"', 'class="brand title-cell"'], false);
                                $response->assertSee('rowspan="2"', false)->assertDontSee('#13538b')->assertDontSee('#b01b7c')->assertDontSee('#fbd0df');
                                $response->assertSee('الفرع الثاني: سوريا – حلب')->assertSee('سعر الوحدة')
                                    ->assertSee('إجمالي المدفوعات')->assertDontSee('DESCRIPTION')->assertDontSee('Total payments')
                                    ->assertSee('rowspan="2" align="right" valign="middle"', false)->assertDontSee('Exchange Policy');
                            } else {
                                $response->assertSeeInOrder(['class="directory"', 'class="brand"', 'class="customer"'], false);
                                $response->assertSee('Branch 1 : UAE – Ajman')->assertSee('DESCRIPTION')
                                    ->assertSee('Total payments')->assertSee('Exchange Policy')
                                    ->assertDontSee('سعر الوحدة')->assertDontSee('رقم الطلب');
                            }
                            if ($source === 'order') {
                                $response->assertSee($seller->name)->assertSee($seller->trn)
                                    ->assertSee($country === Country::SYRIA ? 'فاتورة ضريبية' : 'TAX INVOICE');
                            }
                        }
                    }
                }
            }
        }
    }

    private function assertApprovedCopy(string $html, int $country): void
    {
        preg_match('/<td class="directory"[^>]*>(.*?)<\/td>/s', $html, $match);
        $directory = $match[1];
        $this->assertSame(4, substr_count($directory, 'class="branch"'));
        $this->assertStringNotContainsString('فقط', $directory);
        $this->assertStringNotContainsString('الصناعية', $directory);
        $this->assertStringNotContainsString('Industrial', $directory);
        $this->assertStringNotContainsString('United Arab Emirates', $directory);
        foreach (['+971 545 516 995', '+971 564 533 655', '+963 958 900 555', '+963 947 900 555', '+961 81 730 725', '+905 004 001 621'] as $phone) {
            $this->assertStringContainsString($phone, $directory);
        }
        $this->assertLessThan(strpos($directory, '+963 947 900 555'), strpos($directory, '+963 958 900 555'));
        $this->assertStringContainsString('href="http://www.oneway.fashion"', $directory);
        $this->assertStringContainsString('href="mailto:theoneway.fashion@gmail.com"', $directory);
        $branches = $country === Country::SYRIA ? [
            'الفرع الأول: الإمارات – عجمان', 'الفرع الثاني: سوريا – حلب',
            'الفرع الثالث: لبنان – بيروت', 'الفرع الرابع: تركيا – إسطنبول – مارتر',
        ] : [
            'Branch 1 : UAE – Ajman', 'Branch 2 : Syria – Aleppo',
            'Branch 3 : Lebanon – Beirut', 'Branch 4 : Turkey – Istanbul – Merter',
        ];
        foreach ($branches as $branch) {
            $this->assertStringContainsString($branch, $directory);
        }
        preg_match_all('/<table class="policy"[^>]*>(.*?)<\/table>/s', $html, $policies);
        $this->assertCount($country === Country::SYRIA ? 1 : 2, $policies[1]);
        foreach ($policies[1] as $policy) {
            $this->assertSame(4, substr_count($policy, '<tr>'));
        }
        foreach ([
            'مدة الاستبدال 3 ايام من تاريخ الفاتورة',
            'يجب أن تكون المنتجات المراد استبدالها بحالة جيدة وقابلة للعرض، مع الغلاف والبطاقة الأصلية وإيصال الشراء.',
            'لا يوجد لدينا ترجيع أو استرداد نقدي، ويكون الاستبدال وفق الشروط المذكورة أعلاه.',
            'يجب استلام الطلبية خلال مدة أقصاها 5 أيام من تاريخ الطلب، وفي حال عدم استلامها خلال المدة المحددة تُلغى الطلبية دون استرجاع العربون.',
        ] as $term) {
            $this->assertStringContainsString($term, $html);
        }
        $this->assertStringNotContainsString('يشمل الاستبدال المنتجات المعيبة فقط', $html);
        $this->assertStringNotContainsString('The replacement period', $html);
        $this->assertStringContainsString('سياسة الاستبدال', $html);
        if ($country === Country::UAE) {
            foreach ([
                'Exchange requests are accepted within 3 days from the date of the invoice.',
                'The products to be exchanged must be in good and resalable condition, with the original packaging, tags, and purchase receipt.',
                'We do not offer returns or cash refunds. Exchanges are only accepted in accordance with the conditions stated above.',
                'Orders must be collected within a maximum of 5 days from the order date. If the order is not collected within the specified period, it will be cancelled without a refund of the deposit.',
                'Thank you for choosing One Way.',
            ] as $term) {
                $this->assertStringContainsString($term, $html);
            }
        } else {
            $this->assertStringContainsString('شكرًا لتعاملكم مع One Way', $html);
        }
    }

    public function test_cheque_notice_is_conditional_and_notes_are_preserved_in_the_country_language(): void
    {
        foreach ([Country::UAE, Country::SYRIA] as $country) {
            foreach (['0' => 'cash', '1' => 'card', '2' => 'cheque', 'cod' => 'cod'] as $payment => $key) {
                $order = new WebsiteOrder([
                    'country_id' => $country, 'barcode' => 'PAYMENT-123', 'curr_type' => 'SYP',
                    'payment_type' => (string) $payment, 'notes' => 'ملاحظة إضافية <script>alert(1)</script>',
                    'total_price' => 1234567, 'remain_price' => 1234567,
                ]);
                $order->setRelation('items', collect());
                $data = (new InvoiceDataService())->forOrder($order);
                $html = view('includes.invoice_template', $data)->render();
                $profile = config('invoices.a4_countries.' . $country);
                $this->assertStringContainsString($profile['payments'][$key], $html);
                $this->assertSame($key === 'cheque', str_contains($html, 'بعد 90'));
                $this->assertSame($key === 'cheque' && $country === Country::UAE, str_contains($html, 'Important Notice:'));
                $this->assertStringContainsString('ملاحظة إضافية &lt;script&gt;', $html);
                $this->assertStringNotContainsString('<script>alert', $html);
                $this->assertStringContainsString('1,234,567 SYP', $html);
                $this->assertStringNotContainsString('1,234,567.00', $html);
                $this->assertStringContainsString($profile['footer_phone'], $html);
                $this->assertDoesNotMatchRegularExpression('/[\x{0660}-\x{0669}\x{FFFD}]|ط§|ظ„/u', $html);
            }
        }
    }
}
