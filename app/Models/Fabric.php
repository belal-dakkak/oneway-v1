<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Jenssegers\Date\Date;

class Fabric extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'count', 'yards', 'color', 'image', 'user_id'];

    protected $appends = ['image_url', 'date'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(FabricLog::class);
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
