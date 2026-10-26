<?php

namespace App\Support;

final class ProductColorImage
{
    /**
     * Normalize the media endpoint's legacy array response and the current
     * string value into the path stored on product_colors.
     */
    public static function path($value, ?string $fallback = null): ?string
    {
        while (is_array($value)) {
            if ($value === []) {
                return $fallback;
            }

            $value = reset($value);
        }

        if (!is_string($value)) {
            return $fallback;
        }

        $value = trim($value);

        return $value !== '' ? $value : $fallback;
    }
}
