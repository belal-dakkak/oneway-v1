<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnpaidOrderArchive extends Model
{
    protected $fillable = [
        'order_id', 'actor_id', 'order_barcode', 'currency_code',
        'previous_paid_price', 'previous_remain_price', 'reason',
        'idempotency_key', 'archived_at',
    ];

    protected $casts = [
        'previous_paid_price' => 'float',
        'previous_remain_price' => 'float',
        'archived_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
