<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Models\WebsiteOrder;
use App\Support\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_syrian_a4_preview_and_print_use_the_fixed_directory_while_receipt_stays_unchanged(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin', 'email' => 'invoice-admin@example.test', 'password' => 'secret',
            'role_id' => User::ROLE_ADMIN, 'country_id' => Country::SYRIA,
        ]);
        $shop = User::query()->create([
            'name' => 'Aleppo branch', 'email' => 'shop@example.test', 'phone' => 'shop-phone',
            'password' => 'secret', 'role_id' => User::ROLE_SHOP, 'country_id' => Country::SYRIA,
        ]);
        $key = Setting::keyColumn();
        Setting::query()->create(['country' => Country::SYRIA, 'language' => 'en', $key => 'phone', 'value' => '+963 944 123 456']);
        Setting::query()->create(['country' => Country::SYRIA, 'language' => 'ar', $key => 'email', 'value' => 'syria@example.test']);
        $order = Order::query()->create([
            'seller_id' => $shop->id, 'barcode' => 'SY-PREVIEW-1', 'type' => Order::TYPE_CASH,
            'curr_type' => 'USD', 'curr_rate' => 1, 'total_price' => 5,
            'paid_price' => 5, 'remain_price' => 0,
        ]);

        $this->actingAs($admin)
            ->get(route('invoice.typed.show', ['source' => 'order', 'id' => $order->id]))
            ->assertOk()
            ->assertSee('custom/logo-icon-black.png', false)
            ->assertSee('Aleppo branch')
            ->assertSee('+963 947 900 555')
            ->assertSee('theoneway.fashion@gmail.com')
            ->assertDontSee('syria@example.test')
            ->assertDontSee('shop@example.test');

        $this->actingAs($admin)
            ->get(route('invoice.typed.printv2', ['source' => 'order', 'id' => $order->id]))
            ->assertOk()
            ->assertSee('BILL TO')
            ->assertSee('window.print()', false)
            ->assertSee('+963 947 900 555')
            ->assertSee('theoneway.fashion@gmail.com')
            ->assertDontSee('syria@example.test')
            ->assertDontSee('shop@example.test');

        $this->actingAs($admin)
            ->get(route('invoice.typed.printv2', ['source' => 'order', 'id' => $order->id, 'format' => 'receipt']))
            ->assertOk()
            ->assertSee('id="bodyContent"', false)
            ->assertSee('+963 944 123 456');
    }

    public function test_uae_preview_uses_the_a4_invoice_with_legal_seller_and_buyer_identity(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin UAE', 'email' => 'invoice-uae-admin@example.test', 'password' => 'secret',
            'role_id' => User::ROLE_ADMIN, 'country_id' => Country::UAE,
        ]);
        $shop = User::query()->create([
            'name' => 'One Way UAE', 'email' => 'uae-shop@example.test', 'phone' => '+971 500 000 001',
            'address' => 'Ajman branch', 'password' => 'secret',
            'role_id' => User::ROLE_SHOP, 'country_id' => Country::UAE,
        ]);
        $order = Order::query()->create([
            'seller_id' => $shop->id, 'barcode' => 'AE-PREVIEW-1', 'type' => Order::TYPE_CASH,
            'curr_type' => 'AED', 'curr_rate' => 1, 'total_price' => 50,
            'paid_price' => 50, 'remain_price' => 0,
            'first_name' => 'Crystal', 'last_name' => 'Gift',
            'phone' => '+971 500 000 002', 'address' => 'Sharjah',
        ]);

        $this->actingAs($admin)
            ->get(route('invoice.typed.show', ['source' => 'order', 'id' => $order->id]))
            ->assertOk()
            ->assertSee('BILL TO')
            ->assertSee('One Way UAE')
            ->assertSee('Ajman Industrial 2 Beirut Street')
            ->assertSee('+971 545 516 995')
            ->assertDontSee('uae-shop@example.test')
            ->assertSee('Crystal Gift')
            ->assertSee('+971 500 000 002')
            ->assertSee('Sharjah');
    }

    public function test_lebanon_website_order_uses_the_same_a4_directory_for_view_print_and_download(): void
    {
        $admin = User::query()->create([
            'name' => 'Lebanon Admin', 'email' => 'invoice-lb-admin@example.test', 'password' => 'secret',
            'role_id' => User::ROLE_ADMIN, 'country_id' => Country::LEBANON,
        ]);
        $order = WebsiteOrder::query()->create([
            'barcode' => 'LB-WEB-1', 'country_id' => Country::LEBANON,
            'curr_type' => 'USD', 'curr_rate' => 1, 'payment_type' => 'cod',
            'total_price_before_discount' => 25, 'discount' => 0, 'total_price' => 25,
            'paid_price' => 0, 'remain_price' => 25,
            'first_name' => 'Lebanon', 'last_name' => 'Customer',
            'phone' => '+961 70 000 000', 'address' => 'Beirut',
        ]);

        foreach (['invoice.typed.show', 'invoice.typed.printv2'] as $route) {
            $this->actingAs($admin)
                ->get(route($route, ['source' => 'website', 'id' => $order->id]))
                ->assertOk()
                ->assertSee('Lebanon, Beirut')
                ->assertSee('Ajman Industrial 2 Beirut Street')
                ->assertSee('Syria (Aleppo)')
                ->assertSee('Türkiye')
                ->assertSee('Lebanon Customer');
        }

        $this->actingAs($admin)
            ->get(route('download.invoice.typed', ['source' => 'website', 'id' => $order->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
