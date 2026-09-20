<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Support\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_syrian_preview_and_print_use_website_contacts_without_shop_email(): void
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
            ->assertSee('+963 944 123 456')
            ->assertSee('syria@example.test')
            ->assertDontSee('shop@example.test');

        $this->actingAs($admin)
            ->get(route('invoice.typed.printv2', ['source' => 'order', 'id' => $order->id]))
            ->assertOk()
            ->assertSee('BILL TO')
            ->assertSee('window.print()', false)
            ->assertSee('+963 944 123 456')
            ->assertSee('syria@example.test')
            ->assertDontSee('shop@example.test');

        $this->actingAs($admin)
            ->get(route('invoice.typed.printv2', ['source' => 'order', 'id' => $order->id, 'format' => 'receipt']))
            ->assertOk()
            ->assertSee('id="bodyContent"', false)
            ->assertSee('+963 944 123 456');
    }

    public function test_uae_preview_uses_the_a4_invoice_with_seller_and_buyer_contacts(): void
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
            ->assertSee('Ajman branch')
            ->assertSee('uae-shop@example.test')
            ->assertSee('+971 500 000 001')
            ->assertSee('Crystal Gift')
            ->assertSee('+971 500 000 002')
            ->assertSee('Sharjah');
    }
}
