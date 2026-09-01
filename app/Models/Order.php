<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Jenssegers\Date\Date;

class Order extends Model
{
    use HasFactory;

    const STATUS_PENDING = 1;
    const STATUS_ONGOING = 2;
    const STATUS_DELIVERED = 3;

    const TYPE_CASH = 1;
    const TYPE_FOR_CLIENT = 2;
    const TYPE_APP = 3;

    const PAY_CASH = 0;
    const PAY_CARD = 1;
    const PAY_CHEQUE = 1;
    protected $fillable = ['seller_id', 'buyer_id', 'shipper_id', 'type', 'barcode','total_price_before_discount',
        'discount', 'total_price', 'paid_price', 'remain_price', 'invoice', 'sent_at', 'shipping_details_id',
        'status','payment_type','curr_type','curr_rate','notes',
        'tax_ratio','tax_value','price_without_tax','trn','order_type',
        'first_name', 'last_name', 'email', 'phone', 'address', 'city', 'building_name', 'flat_number',
        'shipping_fee', 'cod_fee',
    ];

    protected $appends = ['date', 'sent_date','payment_label'];

    public function getPaymentLabelAttribute()
    {
        $payment_type = "Pay by Cash";
        if($this->payment_type == 0){
            $payment_type = "Pay by Cash";
        }elseif($this->payment_type == 1){
            $payment_type = "Pay by Credit/Debit Card";
        }elseif($this->payment_type == 2){
            $payment_type = "Pay by Cheque";
        }
        return $payment_type;
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function shipper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shipper_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function productItems(): HasMany
    {
        return $this->hasMany(ProductItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function refund(): HasOne
    {
        return $this->hasOne(Refund::class, 'order_barcode', 'barcode');
    }

    public function shippingDetails(): BelongsTo
    {
        return $this->belongsTo(ShippingDetails::class, 'shipping_details_id');
    }

    public function getDateAttribute()
    {
        Date::setLocale('ar');
        return Date::parse($this->created_at)->timezone(\App\Support\Country::timezone($this->seller->country_id ?? \App\Support\Country::UAE))->format('d-m-Y h:i a');
    }

    public function getSentDateAttribute()
    {
        if ($this->sent_at){
            Date::setLocale('ar');
            return Date::parse($this->sent_at)->timezone(\App\Support\Country::timezone($this->seller->country_id ?? \App\Support\Country::UAE))->format('d-m-Y h:i a');
        }
        return '';
    }

}
