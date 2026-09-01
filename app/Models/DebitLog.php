<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Jenssegers\Date\Date;

class DebitLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_debit_id', 'user_product_id', 'debit_payment_id', 'merchant_refund_id', 'note', 'amount',
        'product_color_id','shop_id','merchant_id','request_date','qty','type'
    ];

    protected $appends = ['date', 'color'];

    public function getDateAttribute()
    {
        Date::setLocale('ar');
        return Date::parse($this->created_at)->timezone('Asia/Dubai')->format('d-m-Y h:i a');
    }

    public function merchantDebit(): BelongsTo
    {
        return $this->belongsTo(MerchantDebit::class, 'merchant_debit_id');
    }

    public function userProduct(): BelongsTo
    {
        return $this->belongsTo(MerchantDebit::class, 'user_product_id');
    }

    public function debitPayment(): BelongsTo
    {
        return $this->belongsTo(DebitPayment::class, 'debit_payment_id');
    }

    public function getColorAttribute()
    {
        if ($this->user_product_id)
            return 'red';
        return 'green';
    }
}
