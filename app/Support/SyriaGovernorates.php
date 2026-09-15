<?php

namespace App\Support;

final class SyriaGovernorates
{
    public const ALL = [
        'دمشق',
        'ريف دمشق',
        'حلب',
        'حمص',
        'حماة',
        'اللاذقية',
        'طرطوس',
        'إدلب',
        'درعا',
        'السويداء',
        'القنيطرة',
        'دير الزور',
        'الرقة',
        'الحسكة',
    ];

    public static function all(): array
    {
        return self::ALL;
    }
}
