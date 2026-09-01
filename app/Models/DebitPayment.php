<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Jenssegers\Date\Date;

class DebitPayment extends Model
{
    use HasFactory;

    protected $fillable = ['merchant_debit_id', 'amount'];

    protected $appends = ['date'];

    public function getDateAttribute()
    {
        Date::setLocale('ar');
        return Date::parse($this->created_at)->timezone('Asia/Dubai')->format('d-m-Y h:i a');
    }

    public function merchantDebit(): BelongsTo
    {
        return $this->belongsTo(MerchantDebit::class, 'merchant_debit_id');
    }


}
