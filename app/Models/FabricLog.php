<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FabricLog extends Model
{
    use HasFactory;

    protected $fillable = ['fabric_id', 'count', 'user_id'];

    protected $appends = ['date'];

    public function fabric(): BelongsTo
    {
        return $this->belongsTo(Fabric::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDateAttribute()
    {
        return Carbon::parse($this->created_at)->toDateString();
    }

}
