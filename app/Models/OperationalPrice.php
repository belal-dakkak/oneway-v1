<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OperationalPrice extends Model
{
    protected $fillable = [
        'priceable_type',
        'priceable_id',
        'currency_code',
        'exchange_rate',
        'cost_price',
        'wholesale_price',
        'retail_price',
        'sale_price',
        'price_before_discount',
    ];

    protected $casts = [
        'exchange_rate' => 'float',
        'cost_price' => 'float',
        'wholesale_price' => 'float',
        'retail_price' => 'float',
        'sale_price' => 'float',
        'price_before_discount' => 'float',
    ];

    public function priceable(): MorphTo
    {
        return $this->morphTo();
    }
}
