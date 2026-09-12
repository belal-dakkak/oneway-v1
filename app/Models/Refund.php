<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Jenssegers\Date\Date;

class Refund extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_item_id', 'qty', 'item_price', 'total_price', 'total_price_paid',
        'currency_code', 'net_amount', 'tax_amount', 'cost_amount',
        'item_barcode', 'order_barcode',
    ];

    protected $casts = [
        'qty' => 'integer',
        'total_price_paid' => 'float',
        'net_amount' => 'float',
        'tax_amount' => 'float',
        'cost_amount' => 'float',
    ];
    protected $appends = [
        'date', 'item_name', 'item_image', 'client_name', 'shop_name',
        'currency_code', 'transaction_item_price', 'transaction_total_price',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function getDateAttribute()
    {
        Date::setLocale('ar');
        return Date::parse($this->created_at)->timezone('Asia/Dubai')->format('d-m-Y h:i a');
    }

    public function getClientNameAttribute()
    {
        return $this->orderItem->order->buyer?$this->orderItem->order->buyer->name:'طلبية سريعة';
    }

    public function getShopNameAttribute()
    {
        return $this->orderItem->order->seller->name;
    }

    public function getItemNameAttribute()
    {
        return $this->orderItem->product->productColor->product_name;
    }

    public function getItemImageAttribute()
    {
        return $this->orderItem->product->productColor->photo_url;
    }

    public function getCurrencyCodeAttribute(): string
    {
        return strtoupper((string) (($this->attributes['currency_code'] ?? null)
            ?: optional(optional($this->orderItem)->order)->curr_type ?: 'USD'));
    }

    public function getTransactionTotalPriceAttribute(): float
    {
        $stored = (float) ($this->attributes['total_price_paid'] ?? 0);
        if ($stored != 0.0) {
            return $stored;
        }

        $rate = (float) (optional(optional($this->orderItem)->order)->curr_rate ?: 1);
        return (float) $this->total_price * $rate;
    }

    public function getTransactionItemPriceAttribute(): float
    {
        return $this->qty > 0
            ? $this->transaction_total_price / (int) $this->qty
            : 0.0;
    }
}
