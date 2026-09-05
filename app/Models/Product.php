<?php

namespace App\Models;

use App\Services\CurrencyService;
use App\Support\Country;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Session;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'name_en', 'details', 'details_en','image', 'category_id', 'country_id', 'barcode', 'sizes','sale_price', 'cost_price', 'retail_price', 'price_before_discount', 'rate','country_id', 'shown_for_merchant'
    ];
    protected $casts = ['sizes' => 'array', 'shown_for_merchant' => 'boolean'];
    protected $appends = ['photo_url', 'raw_sizes', 'image_url','country_name','app_stock', 'final_price', 'final_price_value', 'formatted_price_before_discount', 'price_before_discount_value', 'price_before_discount_raw', 'wholesale_price_value', 'formatted_wholesale_price'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function colors(): HasMany
    {
        return $this->hasMany(ProductColor::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'product_id');
    }

    public function calculateRate()
    {
        $this->rate = $this->reviews()->avg('review');
        $this->save();
    }

    public function isReviewBy(User $user)
    {
        $reviewByMe = $user
            ->reviews()
            ->where('products.id', $this->id)
            ->count();

        if ($reviewByMe) {
            $review_content = Review::query()
                ->where('product_id', $this->id)
                ->where('user_id', $user->id)->first()->setHidden(['user', 'id', 'user_id', 'product_id', 'created_at', 'updated_at']);
            $this->review_content = $review_content;
        }

        return $this->review_content;
    }

    public function isLikedBy(User $user): bool
    {
        return (bool)$user
            ->wishList()
            ->where('products.id', $this->id)
            ->count();
    }
    /**
     * Get the URL to the user's profile photo.
     *
     * @return string
     */
    public function getCountryNameAttribute()
    {
        $country = "no country";
        switch ($this->country_id) {
            case 1:
                $country = "Lebanon";
                break;
            case 2:
                $country = "United Arab Emirates";
                break;
            case 3:
                $country = "All Countries";
                break;
            case 4:
                $country = "Syria";
                break;
        }
        return $country;
    }

    public function getAppStockAttribute()
    {
        // $products = ProductColor::where('product_id',$this->id)->get();
        // $stock = 0;
        // foreach ($products as $key => $product) {
        //     $default = ['stock' => 0, 'size' => '','barcode' => ''];
        //     if(is_null($product->sizes)) return $default;
        //     $sizes = json_decode($product->sizes,true);
        //     if(is_null($sizes)) return $default;
        //     if(count($sizes) == 0) return $default;
        //     foreach ($sizes as $key => $size) {
        //         $stock = UserProduct::where('barcode',$sizes[$key]['barcode'])->sum('stock');
        //         // $sizes[$key]['stock'] = (int)$sizes[$key]['stock'];
        //         $sizes[$key]['stock'] = (int)$stock;
        //     }
        //     foreach ($sizes as $key => $size) {
        //         $stock += $size['stock'];
        //     }
        // }
        // return $stock;
    }
    /**
     * Get the URL to the user's profile photo.
     *
     * @return string
     */
    public function getPhotoUrlAttribute()
    {
        return $this->image
            ? storageImage($this->image)
            : $this->defaultPhotoUrl();
    }

    /**
     * Get the URL to the user's profile photo.
     *
     * @return string
     */
    public function getImageUrlAttribute()
    {
        return $this->colors()->first() ? $this->colors()->first()->photo_url : $this->photo_url;
    }

    /**
     * Get the default photo URL if no photo has been uploaded.
     *
     * @return string
     */
    protected function defaultPhotoUrl(): string
    {
        $name = trim(collect(explode(' ', $this->name))->map(function ($segment) {
            return $segment[0] ?? '';
        })->join(' '));

        return 'https://ui-avatars.com/api/?name='.urlencode($name).'&color=7F9CF5&background=EBF4FF';
    }

    public function getRawSizesAttribute()
    {
        $raw = '';
        foreach ($this->sizes as $key => $size){
            $raw .= $size ;
            if ($key < count($this->sizes)-1)
                $raw .= ' - ';
        }
        return $raw;
    }

    public function getRetailPriceAttribute($val)
    {
        return $val ?: $this->cost_price;
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
        $currency = $this->presentationCurrency();
        $price = app(CurrencyService::class)->fromUsd($this->price_before_discount, $currency);
        return $currency === 'USD' ? round($price, 2) : ceil($price);
    }

    public function getPriceBeforeDiscountRawAttribute()
    {
        return $this->getRawOriginal('price_before_discount');
    }

    public function getPriceBeforeDiscountAttribute($val)
    {
        // Prioritize explicit price_before_discount from products table
        if ($val) {
            return $val;
        }

        // Secondary priority: shadowed attribute from join (if needed, but usually we want local)
        if (isset($this->attributes['user_price_before_discount']) && $this->attributes['user_price_before_discount']) {
            return $this->attributes['user_price_before_discount'];
        }

        // Fake discount logic
        $aedRetailPrice = $this->retail_price * app(CurrencyService::class)->rate('AED');
        if ($aedRetailPrice > 150) {
            return $this->retail_price / 0.4;
        } else {
            return $this->retail_price / 0.5;
        }
    }

    public function getWholesalePriceValueAttribute()
    {
        $currency = $this->presentationCurrency();
        $price = app(CurrencyService::class)->fromUsd($this->sale_price, $currency);
        return $currency === 'USD' ? round($price, 2) : ceil($price);
    }

    public function getFormattedWholesalePriceAttribute()
    {
        $price = $this->wholesale_price_value;
        $currency = $this->presentationCurrency();
        return $currency === 'USD'
            ? number_format($price, 2, '.', '') . ' USD'
            : number_format(ceil($price), 2, '.', '0') . ' AED ';
    }

    private function presentationCurrency(): string
    {
        $countryId = (int) $this->country_id;
        if ((int) request()->input('country_id') === Country::SYRIA) {
            $countryId = Country::SYRIA;
        }
        $requestCountry = request()->header('Accept-Country');
        if ($requestCountry && Country::code($requestCountry) === 'SY') {
            $countryId = Country::SYRIA;
        }
        if ($countryId === Country::SYRIA ||
            ($countryId === Country::globalProductId() && Country::id() === Country::SYRIA)) {
            return 'USD';
        }

        // Preserve the legacy presentation contract for Lebanon and UAE.
        return 'AED';
    }
}
