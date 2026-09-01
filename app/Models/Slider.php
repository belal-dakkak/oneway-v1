<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Slider extends Model
{
    use HasFactory;
    protected $fillable = ['image', 'type'];
    protected $appends = ['image_url','position'];

    const TYPE_FIRST = 1;
    const TYPE_SECOND = 2;
    const TYPE_THIRD = 3;

    public function getImageUrlAttribute()
    {
        return storageImage($this->image);
    }

    public function getPositionAttribute()
    {
        if($this->type == 2) return "Center";
        if($this->type == 3) return "Right";
        return "Left";
    }
}
