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
    protected $fillable = ['order_item_id', 'qty', 'item_price', 'total_price','total_price_paid', 'item_barcode', 'order_barcode'];
    protected $appends = ['date', 'item_name', 'item_image', 'client_name', 'shop_name'];

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
}
