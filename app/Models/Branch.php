<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = ['name_ar', 'name_en', 'address_ar', 'address_en', 'latitude', 'longitude'];

    protected $casts= ['latitude' => 'double', 'longitude' => 'double'];
}
