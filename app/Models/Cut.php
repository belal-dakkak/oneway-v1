<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cut extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'image', 'size', 'total', 'sizes', 'colors', 'cut_date'];

    protected $casts = ['sizes' => 'array', 'colors' => 'array'];

    protected $appends = ['raw_sizes', 'raw_colors', 'image_url'];

    public function getImageUrlAttribute()
    {
        return storageImage($this->image);
    }

    public function getRawSizesAttribute()
    {
        $raw = '';
        foreach ($this->sizes as $key => $size){
            $raw .= $size;
            if ($key < count($this->sizes)-1)
                $raw .= ' - ';
        }
        return $raw;
    }

    public function getRawColorsAttribute()
    {
        $raw = '';
        foreach ($this->colors as $key => $size){
            $raw .= $size ;
            if ($key < count($this->colors)-1)
                $raw .= ' - ';
        }
        return $raw;
    }

}
