<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = ['image', 'type', 'related_id'];

    protected $with = ['related', 'related.colors'];

    protected $appends = ['image_url'];

    const TYPE_NONE = 1;
    const TYPE_CATEGORY = 2;
    const TYPE_PRODUCT = 3;

    public function related(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'related_id');
    }

    public function getImageUrlAttribute()
    {
        return storageImage($this->image);
    }
}
