<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantRefund extends Model
{
    use HasFactory;

    protected $fillable = ['user_product_id', 'merchant_id', 'merchant_debit_id', 'qty'];

    public function userProduct(): BelongsTo
    {
        return $this->belongsTo(UserProduct::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function merchantDebit(): BelongsTo
    {
        return $this->belongsTo(MerchantDebit::class);
    }

}
