<?php

namespace Tests\Feature;

use App\Models\MobileSlider;
use App\Models\Slider;
use App\Services\StorefrontSliderService;
use App\Support\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontSliderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_country_sliders_replace_global_sliders_only_for_that_country(): void
    {
        Slider::query()->create(['image' => 'global.jpg', 'type' => 1, 'country_id' => null]);
        Slider::query()->create(['image' => 'syria.jpg', 'type' => 1, 'country_id' => Country::SYRIA]);
        MobileSlider::query()->create(['image' => 'global-mobile.jpg', 'type' => 1, 'country_id' => null]);
        MobileSlider::query()->create(['image' => 'syria-mobile.jpg', 'type' => 1, 'country_id' => Country::SYRIA]);

        $service = app(StorefrontSliderService::class);

        $this->assertSame(['syria.jpg'], $service->desktop(Country::SYRIA)->pluck('image')->all());
        $this->assertSame(['global.jpg'], $service->desktop(Country::UAE)->pluck('image')->all());
        $this->assertSame(['syria-mobile.jpg'], $service->mobile(Country::SYRIA)->pluck('image')->all());
        $this->assertSame(['global-mobile.jpg'], $service->mobile(Country::LEBANON)->pluck('image')->all());
    }
}
