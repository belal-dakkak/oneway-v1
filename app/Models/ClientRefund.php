<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientRefund extends Model
{
    use HasFactory;

    protected $fillable = ['refund_id', 'client_id', 'client_debit_id'];

    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function debit(): BelongsTo
    {
        return $this->belongsTo(ClientDebit::class, 'client_debit_id');
    }
}
