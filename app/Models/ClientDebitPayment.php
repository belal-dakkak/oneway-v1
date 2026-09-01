<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Jenssegers\Date\Date;

class ClientDebitPayment extends Model
{
    use HasFactory;

    protected $fillable = ['client_debit_id', 'amount'];

    public function clientDebit(): BelongsTo
    {
        return $this->belongsTo(ClientDebit::class);
    }

    protected $appends = ['date'];

    public function getDateAttribute()
    {
        Date::setLocale('ar');
        return Date::parse($this->created_at)->timezone('Asia/Dubai')->format('d-m-Y h:i a');
    }
}
