<?php

namespace App\Models;

use App\Services\CurrencyService;
use App\Support\Country;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class UserProduct extends Model
{
    use HasFactory;

    protected $fillable = ['product_color_id', 'product_size_id', 'user_id', 'merchant_id', 'stock', 'wholesale_price', 'retail_price', 'price_before_discount', 'approved','size','barcode','country_id'];

    protected $appends = ['date', 'final_price', 'final_price_value', 'wholesale_price_value', 'formatted_price_before_discount', 'price_before_discount_value'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    public function productColor(): BelongsTo
    {
        return $this->belongsTo(ProductColor::class, 'product_color_id');
    }

    public function productSize(): BelongsTo
    {
        return $this->belongsTo(ProductSize::class, 'product_size_id');
    }

    public function log(): HasMany
    {
        return $this->hasMany(UserProductLog::class, 'user_product_id');
    }

    public function operationalPrices(): MorphMany
    {
        return $this->morphMany(OperationalPrice::class, 'priceable');
    }

    public function getDateAttribute()
    {
        return Carbon::parse($this->created_at)->toDateString();
    }

    public function getFinalPriceAttribute()
    {
        $subtotal = $this->final_price_value;
        $currency = $this->presentationCurrency();
        return app(\App\Services\OperationalPriceService::class)->format($subtotal, $currency) . ' ' . $currency;
    }

    public function getFinalPriceValueAttribute()
    {
        $currency = $this->presentationCurrency();
        return app(\App\Services\OperationalPriceService::class)
            ->amount($this, 'retail_price', $currency, $this->attributes['retail_price'] ?? 0);
    }

    public function getWholesalePriceValueAttribute()
    {
        $currency = $this->presentationCurrency();
        return app(\App\Services\OperationalPriceService::class)
            ->amount($this, 'wholesale_price', $currency, $this->attributes['wholesale_price'] ?? 0);
    }

    public function getFormattedPriceBeforeDiscountAttribute()
    {
        $price = $this->price_before_discount_value;
        $currency = $this->presentationCurrency();
        return app(\App\Services\OperationalPriceService::class)->format($price, $currency) . ' ' . $currency;
    }

    public function getPriceBeforeDiscountValueAttribute()
    {
        if ($this->price_before_discount) {
            $currency = $this->presentationCurrency();
            return app(\App\Services\OperationalPriceService::class)
                ->amount($this, 'price_before_discount', $currency, $this->attributes['price_before_discount'] ?? 0);
        }
        $currency = $this->presentationCurrency();
        $price = app(CurrencyService::class)->fromUsd($this->retail_price * 1.3, $currency);
        return app(\App\Services\OperationalPriceService::class)->normalizeInput($price, $currency);
    }

    private function presentationCurrency(): string
    {
        $countryId = (int) ($this->country_id ?: optional($this->user)->country_id);
        return Country::defaultCurrency($countryId) ?: 'USD';
    }
}
