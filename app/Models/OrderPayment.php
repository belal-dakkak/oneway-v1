<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPayment extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'pay_amount'];

    protected $appends = ['date'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function getDateAttribute()
    {
        return Carbon::parse($this->created_at)->toDateString();
    }
}
