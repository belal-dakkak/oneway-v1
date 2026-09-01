<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FcmNotification extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'title', 'title_ar', 'message', 'message_ar'];
}
