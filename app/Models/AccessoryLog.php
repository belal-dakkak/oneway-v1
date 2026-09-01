<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessoryLog extends Model
{
    use HasFactory;

    protected $fillable = ['accessory_id', 'count', 'user_id'];

    protected $appends = ['date'];

    public function accessory(): BelongsTo
    {
        return $this->belongsTo(Accessory::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDateAttribute()
    {
        return Carbon::parse($this->created_at)->toDateString();
    }

}
