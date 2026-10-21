<?php

namespace App\Services;

use App\Models\MobileSlider;
use App\Models\Slider;
use App\Support\Country;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class StorefrontSliderService
{
    public function desktop(int $countryId): Collection
    {
        return $this->forCountry(Slider::class, 'home_sliders', $countryId);
    }

    public function mobile(int $countryId): Collection
    {
        return $this->forCountry(MobileSlider::class, 'mobile_home_sliders', $countryId);
    }

    public function clear(): void
    {
        Cache::forget('home_sliders');
        Cache::forget('mobile_home_sliders');
        foreach (Country::allowedIds() as $countryId) {
            Cache::forget('home_sliders_'.$countryId);
            Cache::forget('mobile_home_sliders_'.$countryId);
        }
    }

    private function forCountry(string $model, string $prefix, int $countryId): Collection
    {
        return Cache::remember($prefix.'_'.$countryId, 3600, function () use ($model, $countryId) {
            $specific = $model::query()
                ->where('country_id', $countryId)
                ->orderBy('id')
                ->get();

            return $specific->isNotEmpty()
                ? $specific
                : $model::query()->whereNull('country_id')->orderBy('id')->get();
        });
    }
}
