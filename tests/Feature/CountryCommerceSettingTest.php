<?php

namespace Tests\Feature;

use App\Models\CountryCommerceSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CountryCommerceSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_uae_card_checkout_requires_matching_key_and_https_endpoints_but_not_a_cashbox(): void
    {
        $commerce = CountryCommerceSetting::forCountry(User::COUNTRY_UAE);
        $commerce->update([
            'card_enabled' => true,
            'gateway_mode' => 'sandbox',
            'gateway_currency' => 'AED',
            'website_cashbox_user_id' => null,
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

    public function test_syria_is_cod_only_even_if_an_old_setting_enables_cards(): void
    {
        $commerce = CountryCommerceSetting::forCountry(User::COUNTRY_SYRIA);
        $commerce->update([
            'card_enabled' => true,
            'gateway_mode' => 'sandbox',
        ]);
        config([
            'services.tap.secret_key' => 'sk_test_example',
            'services.tap.callback_url' => 'https://test.oneway.fashion/payment/callback',
            'services.tap.webhook_url' => 'https://test.oneway.fashion/payment/webhook',
        ]);

        $this->assertSame('country_cod_only', $commerce->fresh()->cardUnavailableReason());
        $this->assertFalse($commerce->fresh()->cardIsAvailable());
    }
}
