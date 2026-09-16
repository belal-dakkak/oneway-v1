<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name','key','value','language','country'];

    /** Older installations use `name`; the checked-in migration uses `key`. */
    public static function keyColumn(): string
    {
        return Schema::hasColumn('settings', 'name') ? 'name' : 'key';
    }

    public static function valuesFor(int $country, string $language): array
    {
        return static::query()
            ->where('country', $country)
            ->where('language', $language)
            ->pluck('value', static::keyColumn())
            ->toArray();
    }
}
