<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientDebtReconciliation extends Model
{
    protected $fillable = [
        'client_debit_id', 'order_id', 'actor_id', 'currency_code',
        'previous_paid_price', 'previous_remain_price', 'reconciled_amount',
        'account_balance_before', 'account_balance_after', 'reason', 'idempotency_key',
    ];

    protected $casts = [
        'previous_paid_price' => 'float',
        'previous_remain_price' => 'float',
        'reconciled_amount' => 'float',
        'account_balance_before' => 'float',
        'account_balance_after' => 'float',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(ClientDebit::class, 'client_debit_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
