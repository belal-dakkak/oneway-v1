<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Jenssegers\Date\Date;

class Debit extends Model
{
    use HasFactory;

    const TYPE_MERCHANT = 0;
    const TYPE_CLIENT = 1;

    protected $appends = ['date'];

    protected $fillable = ['creditor_id', 'debtor_id', 'type', 'amount', 'order_id', 'user_product_id', 'paid_at', 'user_product_log_id'];

    public function creditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creditor_id');
    }

    public function debtor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'debtor_id');
    }

    public function userProduct(): BelongsTo
    {
        return $this->belongsTo(UserProduct::class, 'user_product_id');
    }

    public function userProductLog(): BelongsTo
    {
        return $this->belongsTo(UserProductLog::class, 'user_product_log_id');
    }

    public function getDateAttribute()
    {
        Date::setLocale('ar');
        return Date::parse($this->created_at)->timezone('Asia/Dubai')->format('d-m-Y h:i a');
    }

}
