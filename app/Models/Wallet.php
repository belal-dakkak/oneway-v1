<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'currency_code', 'credit', 'debit'];

    protected $casts = [
        'credit' => 'float',
        'debit' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movements()
    {
        return $this->hasMany(WalletMovement::class);
    }


}
