<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientDebit extends Model
{
    use HasFactory;

    protected $fillable = ['creditor_id', 'debtor_id', 'amount'];

    public function payments(): HasMany
    {
        return $this->hasMany(ClientDebitPayment::class, 'client_debit_id');
    }

    public function creditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creditor_id');
    }

    public function debtor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'debtor_id');
    }

    public function log(): HasMany
    {
        return $this->hasMany(ClientDebitLog::class, 'client_debit_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(ClientRefund::class, 'client_debit_id');
    }

}
