<?php

namespace App\Models;

use App\Services\CurrencyService;
use App\Support\Country;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class UserProduct extends Model
{
    use HasFactory;

    protected $fillable = ['product_color_id', 'product_size_id', 'user_id', 'merchant_id', 'stock', 'wholesale_price', 'retail_price', 'price_before_discount', 'approved','size','barcode','country_id'];

    protected $appends = ['date', 'final_price', 'final_price_value', 'formatted_price_before_discount', 'price_before_discount_value'];

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

    public function getDateAttribute()
    {
        return Carbon::parse($this->created_at)->toDateString();
    }

    public function getFinalPriceAttribute()
    {
        $subtotal = $this->final_price_value;
        $currency = $this->presentationCurrency();
        return $currency === 'USD'
            ? number_format($subtotal, 2, '.', '') . ' USD'
            : number_format(ceil($subtotal), 2, '.', '0') . ' AED ';
    }

    public function getFinalPriceValueAttribute()
    {
        $currency = $this->presentationCurrency();
        $subtotal = app(CurrencyService::class)->fromUsd($this->retail_price, $currency);
        return $currency === 'USD' ? round($subtotal, 2) : ceil($subtotal);
    }

    public function getFormattedPriceBeforeDiscountAttribute()
    {
        $price = $this->price_before_discount_value;
        $currency = $this->presentationCurrency();
        return $currency === 'USD'
            ? number_format($price, 2, '.', '') . ' USD'
            : number_format(ceil($price), 2, '.', '0') . ' AED ';
    }

    public function getPriceBeforeDiscountValueAttribute()
    {
        if ($this->price_before_discount) {
            $currency = $this->presentationCurrency();
            $price = app(CurrencyService::class)->fromUsd($this->price_before_discount, $currency);
            return $currency === 'USD' ? round($price, 2) : ceil($price);
        }
        $currency = $this->presentationCurrency();
        $price = app(CurrencyService::class)->fromUsd($this->retail_price * 1.3, $currency);
        return $currency === 'USD' ? round($price, 2) : ceil($price);
    }

    private function presentationCurrency(): string
    {
        $countryId = (int) ($this->country_id ?: optional($this->user)->country_id);
        return $countryId === Country::SYRIA ? 'USD' : 'AED';
    }
}
