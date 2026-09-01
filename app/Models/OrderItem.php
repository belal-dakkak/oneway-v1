<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'user_product_id', 'qty','tax_ratio',
        'item_price', 'total_price','tax_value','price_without_tax',
        'item_price_paid', 'total_price_paid','tax_value_paid','price_without_tax_paid'

    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(UserProduct::class, 'user_product_id');
    }
	
	public function user_product(): BelongsTo
    {
        return $this->belongsTo(UserProduct::class, 'user_product_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class, 'order_item_id', 'id');
    }
}
