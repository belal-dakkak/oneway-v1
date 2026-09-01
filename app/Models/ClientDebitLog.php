<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Jenssegers\Date\Date;

class ClientDebitLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_debit_id', 'order_id', 'client_debit_payment_id', 'client_refund_id', 'note', 'amount',
        'request_date','qty','product_color_id','client_id','shop_id'
    ];

    protected $appends = ['date', 'color'];

    public function getDateAttribute()
    {
        Date::setLocale('ar');
        return Date::parse($this->updated_at)->timezone('Asia/Dubai')->format('d-m-Y h:i a');
    }

    public function getColorAttribute()
    {
        if ($this->client_debit_payment_id || $this->client_refund_id)
            return 'green';
        return 'red';
    }

    public function debit(): BelongsTo
    {
        return $this->belongsTo(ClientDebit::class, 'client_debit_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(ClientDebitPayment::class, 'client_debit_payment_id');
    }

    public function refund(): BelongsTo
    {
        return $this->belongsTo(ClientRefund::class, 'client_refund_id');
    }


}
