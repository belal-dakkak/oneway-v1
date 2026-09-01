<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductColor extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'image', 'color_id', 'stock', 'barcode','sizes','country_id'];
    protected $appends = ['photo_url', 'color_name', 'color_code', 'product_name', 'simple_name', 'available_sizes', 'list_sizes', 'clone_list_sizes', 'available_sizes_without_stock', 'product_name_without_barcode', 'app_list_sizes'];

    protected $casts = ['stock' => 'integer'];
    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function userProducts(): HasMany
    {
        return $this->hasMany(UserProduct::class, 'product_color_id');
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

    public function getColorNameAttribute()
    {
        $lang = request()->header('Accept-Language', 'en');
        if ($lang === 'en') {
            return $this->color->name_en;
        }else {
            return $this->color->name;
        }

    }

    public function getColorCodeAttribute()
    {
        return $this->color->code;
    }

    public function getListSizesAttribute()
    {
        $default = ['stock' => 0, 'size' => '','barcode' => ''];
        if(is_null($this->sizes)) return $default;
        $sizes = json_decode($this->sizes,true);
        if (!auth()->check()) return $default;
        if(is_null($sizes)) return $default;
        if(count($sizes) == 0) return $default;
        foreach ($sizes as $key => $size) {
            $nsize = 0;
            $nsizes = array('XS','S','M','L','XL','XXL','3XL','4XL','5XL','6XL','7XL','8XL');
            if(!in_array($size['size'],$sizes)) $nsize = $size['size'];
            else{
                $index = array_search($size['size'], $nsizes);
                $nsize = $index+59;
            }
            if(!isset($sizes[$key]['barcode'])){
                $subsubProductBarcode = $this->barcode.$nsize;
                $sizes[$key]['barcode'] = $subsubProductBarcode;
            }
        }
        $this->sizes = json_encode($sizes);
        $this->save();
        $sizes = json_decode($this->sizes,true);
        foreach ($sizes as $key => $size) {
            $sizes[$key]['barcode'] = $size['barcode'];
            $sizes[$key]['id'] = $this->id;
            $sizes[$key]['size'] = $size['size'];
            $sizes[$key]['color'] = $this->color;
            $sizes[$key]['photo_url'] = $this->photo_url;
        }
        return $sizes;
    }

    public function getCloneListSizesAttribute()
    {
        $default = ['stock' => 0, 'size' => '','barcode' => ''];
        if(is_null($this->sizes)) return $default;
        $sizes = json_decode($this->sizes,true);
        if(is_null($sizes)) return $default;
        if(count($sizes) == 0) return $default;
        foreach ($sizes as $key => $size) {

            $sizes[$key]['stock'] = (int)$sizes[$key]['stock'];
        }
        return $sizes;
    }

    public function getAppListSizesAttribute()
    {
        $default = ['stock' => 0, 'size' => '','barcode' => ''];
        if(is_null($this->sizes)) return $default;
        $sizes = json_decode($this->sizes,true);
        if(is_null($sizes)) return $default;
        if(count($sizes) == 0) return $default;
        foreach ($sizes as $key => $size) {
            $stock = UserProduct::where('barcode',$sizes[$key]['barcode'])->sum('stock');
            // $sizes[$key]['stock'] = (int)$sizes[$key]['stock'];
            $sizes[$key]['stock'] = (int)$stock;
        }
        return $sizes;
    }

    public function getAvailableSizesAttribute()
    {
        if(is_null($this->sizes)) return "";
        $sizes = json_decode($this->sizes,true);
        if(is_null($sizes)) return "";
        if(count($sizes) == 0) return "";
        $response = array();
        foreach ($sizes as $size) {
            try {
                array_push($response, $size['size'] ." - ".$size['stock']);
            } catch (\Throwable $th) {
                continue;
            }
            // $sizes = array_column($sizes, 'size');
        }
        return implode(' || ',$response);
    }
    public function getAvailableSizesWithoutStockAttribute()
    {
        if(is_null($this->sizes)) return "";
        $sizes = json_decode($this->sizes,true);
        if(is_null($sizes)) return "";
        if(count($sizes) == 0) return "";
        $sizes = array_column($sizes, 'size');
        return implode(' || ',$sizes);
    }

    public function getProductNameAttribute()
    {
        return $this->product->name_en. " -" . $this->color->name. "-" . $this->barcode;
    }
    public function getProductNameWithoutBarcodeAttribute()
    {
        return $this->product->name_en. " -" . $this->color->name;
    }
    public function getSimpleNameAttribute()
    {
        return $this->product->name_en. " - " . $this->color->name;
    }

}
