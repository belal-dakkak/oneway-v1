<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Session;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'credit', 'debit'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


}
