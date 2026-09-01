<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MerchantDebit extends Model
{
    use HasFactory;

    protected $fillable = ['creditor_id', 'debtor_id', 'amount'];

    public function payments(): HasMany
    {
        return $this->hasMany(DebitPayment::class, 'merchant_debit_id');
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
        return $this->hasMany(DebitLog::class, 'merchant_debit_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(MerchantRefund::class, 'merchant_debit_id');
    }
}
