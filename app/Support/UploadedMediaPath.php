<?php

namespace App\Support;

final class UploadedMediaPath
{
    /**
     * Normalize both the current string response and legacy nested arrays
     * returned by the shared asynchronous media uploader.
     */
    public static function normalize($value, ?string $fallback = null): ?string
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
