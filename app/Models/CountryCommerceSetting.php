<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountryCommerceSetting extends Model
{
    protected $fillable = [
        'country_id',
        'shipping_fee_usd',
        'free_shipping_threshold_usd',
        'cod_fee_percent',
        'card_enabled',
        'gateway_currency',
        'gateway_mode',
        'website_cashbox_user_id',
        'website_stock_user_id',
    ];

    protected $casts = [
        'country_id' => 'integer',
        'shipping_fee_usd' => 'float',
        'free_shipping_threshold_usd' => 'float',
        'cod_fee_percent' => 'float',
        'card_enabled' => 'boolean',
    ];

    public static function forCountry(int $countryId): self
    {
        return self::query()->firstOrCreate(['country_id' => $countryId], [
            'shipping_fee_usd' => 0,
            'free_shipping_threshold_usd' => null,
            'cod_fee_percent' => 0,
            'card_enabled' => false,
            'gateway_currency' => 'USD',
            'gateway_mode' => 'sandbox',
            'website_cashbox_user_id' => null,
            'website_stock_user_id' => null,
        ]);
    }

    public function cardIsAvailable(): bool
    {
        return $this->cardUnavailableReason() === null;
    }

    public function cardUnavailableReason(): ?string
    {
        $secret = (string) config('services.tap.secret_key');
        if (!$this->card_enabled) {
            return 'disabled';
        }
        if (!$this->website_cashbox_user_id) {
            return 'cashbox_missing';
        }
        if ($secret === '') {
            return 'secret_missing';
        }
        if (!User::query()
            ->whereKey($this->website_cashbox_user_id)
            ->where('country_id', $this->country_id)
            ->whereIn('role_id', [User::ROLE_SHOP, User::ROLE_WAREHOUSE])
            ->exists()) {
            return 'cashbox_invalid';
        }

        $mode = strtolower((string) $this->gateway_mode);
        if (app()->environment(['production', 'live'])) {
            if ($mode !== 'live' || strpos($secret, 'sk_live_') !== 0) {
                return 'mode_or_key_mismatch';
            }
        } elseif (($mode === 'sandbox' && strpos($secret, 'sk_test_') !== 0)
            || ($mode === 'live' && strpos($secret, 'sk_live_') !== 0)
            || !in_array($mode, ['sandbox', 'live'], true)) {
            return 'mode_or_key_mismatch';
        }

        foreach (['callback_url', 'webhook_url'] as $key) {
            $url = (string) config('services.tap.' . $key);
            if (!$url || strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https'
                || !parse_url($url, PHP_URL_HOST)) {
                return 'endpoint_invalid';
            }
        }

        return null;
    }
}
