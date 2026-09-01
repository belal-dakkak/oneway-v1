<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Jenssegers\Date\Date;

class Accessory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'count', 'color', 'image', 'user_id'];

    protected $appends = ['image_url', 'date'];

    public function warehouse()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AccessoryLog::class, 'accessory_id');
    }

    public function getImageUrlAttribute()
    {
        return storageImage($this->image);
    }

    public function getDateAttribute()
    {
        Date::setLocale('ar');
        return Date::parse($this->created_at)->timezone('Asia/Dubai')->format('d-m-Y h:i a');
    }

}
