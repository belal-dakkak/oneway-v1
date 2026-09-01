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
    ];

    protected $casts = [
        'country_id' => 'integer',
        'shipping_fee_usd' => 'float',
        'free_shipping_threshold_usd' => 'float',
        'cod_fee_percent' => 'float',
    ];

    public static function forCountry(int $countryId): self
    {
        return self::query()->firstOrCreate(['country_id' => $countryId], [
            'shipping_fee_usd' => 0,
            'free_shipping_threshold_usd' => null,
            'cod_fee_percent' => 0,
        ]);
    }
}
