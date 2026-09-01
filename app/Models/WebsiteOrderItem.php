<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'website_order_id', 'product_color_id', 'size', 'qty', 
        'item_price', 'item_price_before_discount', 
        'total_price', 'total_price_before_discount'
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(WebsiteOrder::class, 'website_order_id');
    }

    /**
     * Relationship to ProductColor.
     * Named 'product' for simple access, but also adding 'product_color' for frontend compatibility.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductColor::class, 'product_color_id');
    }

    /**
     * Frontend compatibility: WebsiteOrders.vue expects product.product_color
     */
    public function getProductColorAttribute()
    {
        return $this->product;
    }

    protected $appends = ['product_color'];
}
