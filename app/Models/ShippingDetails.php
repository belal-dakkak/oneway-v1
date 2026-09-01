<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'city_id',
        'address',
        'phone',
        'apartment',
        'building',
        'label',
        'post_code',
        'main'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
