<?php

namespace Tests\Feature;

use App\Models\CountryCommerceSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CountryCommerceSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_sandbox_card_checkout_requires_matching_key_cashbox_and_https_endpoints(): void
    {
        $cashbox = User::query()->create([
            'name' => 'UAE Website Cashbox',
            'email' => 'uae-cashbox@example.test',
            'password' => 'secret',
            'role_id' => User::ROLE_SHOP,
            'country_id' => User::COUNTRY_UAE,
        ]);
        $commerce = CountryCommerceSetting::forCountry(User::COUNTRY_UAE);
        $commerce->update([
            'card_enabled' => true,
            'gateway_mode' => 'sandbox',
            'gateway_currency' => 'AED',
            'website_cashbox_user_id' => $cashbox->id,
        ]);

        config([
            'services.tap.secret_key' => 'sk_test_example',
            'services.tap.callback_url' => 'https://test.oneway.fashion/payment/callback',
            'services.tap.webhook_url' => 'https://test.oneway.fashion/payment/webhook',
        ]);
        $this->assertTrue($commerce->fresh()->cardIsAvailable());

        config(['services.tap.secret_key' => 'sk_live_example']);
        $this->assertSame('mode_or_key_mismatch', $commerce->fresh()->cardUnavailableReason());

        config([
            'services.tap.secret_key' => 'sk_test_example',
            'services.tap.callback_url' => 'http://test.oneway.fashion/payment/callback',
        ]);
        $this->assertSame('endpoint_invalid', $commerce->fresh()->cardUnavailableReason());
    }

    public function test_cashbox_must_belong_to_the_same_country(): void
    {
        $foreignCashbox = User::query()->create([
            'name' => 'Syrian Cashbox',
            'email' => 'sy-cashbox@example.test',
            'password' => 'secret',
            'role_id' => User::ROLE_SHOP,
            'country_id' => User::COUNTRY_SYRIA,
        ]);
        $commerce = CountryCommerceSetting::forCountry(User::COUNTRY_UAE);
        $commerce->update([
            'card_enabled' => true,
            'gateway_mode' => 'sandbox',
            'website_cashbox_user_id' => $foreignCashbox->id,
        ]);
        config([
            'services.tap.secret_key' => 'sk_test_example',
            'services.tap.callback_url' => 'https://test.oneway.fashion/payment/callback',
            'services.tap.webhook_url' => 'https://test.oneway.fashion/payment/webhook',
        ]);

        $this->assertSame('cashbox_invalid', $commerce->fresh()->cardUnavailableReason());
    }
}
