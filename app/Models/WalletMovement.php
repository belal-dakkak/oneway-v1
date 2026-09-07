<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletMovement extends Model
{
    protected $fillable = [
        'wallet_id', 'user_id', 'currency_code', 'direction', 'amount',
        'exchange_rate', 'base_amount', 'balance_after', 'payment_method',
        'source_type', 'source_id', 'exchange_group', 'idempotency_key', 'note',
    ];

    protected $casts = [
        'amount' => 'float',
        'exchange_rate' => 'float',
        'base_amount' => 'float',
        'balance_after' => 'float',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
