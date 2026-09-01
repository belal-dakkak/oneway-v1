<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Jenssegers\Date\Date;

class UserProductLog extends Model
{
    use HasFactory;

    protected $fillable = ['user_product_id', 'note', 'approved'];

    protected $appends = ['image', 'date'];

    public function userProduct(): BelongsTo
    {
        return $this->belongsTo(UserProduct::class, 'user_product_id');
    }

    public function getImageAttribute()
    {
        return $this->defaultPhotoUrl();
    }

    public function getDateAttribute()
    {
        Date::setLocale('ar');
        return Date::parse($this->created_at)->timezone('Asia/Dubai')->format('d-m-Y h:i a');
    }

    protected function defaultPhotoUrl(): string
    {
        return 'https://ui-avatars.com/api/?name='.urlencode($this->id).'&color=7F9CF5&background=EBF4FF';
    }
}
