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
                'first_name' => 'Stored customer', 'address' => 'عنوان العميل المخزن', 'phone' => '+963900000000',
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
                                ->assertSee('Stored customer')->assertSee('عنوان العميل المخزن')->assertSee('USD');
                            if ($country === Country::SYRIA) {
                                $response->assertSee('الفرع الثاني: سوريا - حلب')->assertSee('سعر الوحدة')
                                    ->assertSee('إجمالي المدفوعات')->assertDontSee('DESCRIPTION')->assertDontSee('Total payments')
                                    ->assertSee('خلال 3 أيام')->assertDontSee('The replacement period');
                            } else {
                                $response->assertSee('Ajman Industrial 2 Beirut Street')->assertSee('DESCRIPTION')
                                    ->assertSee('Total payments')->assertSee('خلال 5 أيام')
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
