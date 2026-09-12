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
        $secret = (string) config('services.tap.secret_key');
        if (!$this->card_enabled || !$this->website_cashbox_user_id || $secret === '') {
            return false;
        }
        if (!User::query()
            ->whereKey($this->website_cashbox_user_id)
            ->where('country_id', $this->country_id)
            ->whereIn('role_id', [User::ROLE_SHOP, User::ROLE_WAREHOUSE])
            ->exists()) {
            return false;
        }

        if (app()->environment(['production', 'live'])) {
            return $this->gateway_mode === 'live' && strpos($secret, 'sk_live_') === 0;
        }

        return $this->gateway_mode === 'sandbox' || strpos($secret, 'sk_live_') === 0;
    }
}
