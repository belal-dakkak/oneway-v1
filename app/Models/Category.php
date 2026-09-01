<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Category extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'name_en', 'image'];

    protected $appends = ['image_url'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function colors(): HasManyThrough
    {
        return $this->hasManyThrough(ProductColor::class, Product::class);
    }

    public function getImageUrlAttribute()
    {
        if ($this->image)
            return storageImage($this->image);
        return asset('assets/sample-2.webp');
    }

}
