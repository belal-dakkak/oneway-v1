<?php

namespace Tests\Feature;

use App\Models\MobileSlider;
use App\Models\Slider;
use App\Models\User;
use App\Services\StorefrontSliderService;
use App\Support\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SliderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_desktop_syria_slider_accepts_string_images_and_clears_storefront_cache(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('sliders.store'), [
            'place' => ['name' => 'Left', 'value' => '1'],
            'country' => ['name' => 'Syria', 'value' => (string) Country::SYRIA],
            'image' => '/products/colors/syria-old.jpg',
        ])->assertRedirect(route('sliders.index'));

        $slider = Slider::query()->firstOrFail();
        $this->assertSame(Country::SYRIA, (int) $slider->country_id);
        $this->assertSame('/products/colors/syria-old.jpg', $slider->image);
        $this->assertSame(
            ['/products/colors/syria-old.jpg'],
            app(StorefrontSliderService::class)->desktop(Country::SYRIA)->pluck('image')->all()
        );

        $this->put(route('sliders.update', $slider), [
            'place' => ['name' => 'Center', 'value' => '2'],
            'country' => ['name' => 'Syria', 'value' => (string) Country::SYRIA],
            'image' => '/products/colors/syria-new.jpg',
        ])->assertRedirect(route('sliders.index'));

        $slider->refresh();
        $this->assertSame(2, (int) $slider->type);
        $this->assertSame('/products/colors/syria-new.jpg', $slider->image);
        $this->assertSame(
            ['/products/colors/syria-new.jpg'],
            app(StorefrontSliderService::class)->desktop(Country::SYRIA)->pluck('image')->all()
        );
    }

    public function test_mobile_slider_accepts_legacy_array_image_and_preserves_it_when_omitted(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('mobile-sliders.store'), [
            'place' => ['name' => 'Right', 'value' => '3'],
            'country' => ['name' => 'Syria', 'value' => (string) Country::SYRIA],
            'image' => ['/products/colors/syria-mobile.jpg'],
        ])->assertRedirect(route('mobile-sliders.index'));

        $slider = MobileSlider::query()->firstOrFail();
        $this->assertSame('/products/colors/syria-mobile.jpg', $slider->image);

        $this->put(route('mobile-sliders.update', $slider), [
            'place' => ['name' => 'Left', 'value' => '1'],
            'country' => ['name' => 'All Countries', 'value' => ''],
        ])->assertRedirect(route('mobile-sliders.index'));

        $slider->refresh();
        $this->assertSame(1, (int) $slider->type);
        $this->assertNull($slider->country_id);
        $this->assertSame('/products/colors/syria-mobile.jpg', $slider->image);
    }

    public function test_invalid_slider_input_is_rejected_without_mutating_the_record(): void
    {
        $this->actingAs($this->admin());
        $slider = Slider::query()->create([
            'image' => 'unchanged.jpg',
            'type' => 1,
            'country_id' => Country::SYRIA,
        ]);

        $this->put(route('sliders.update', $slider), [
            'place' => ['value' => '9'],
            'country' => ['value' => '3'],
            'image' => 'changed.jpg',
        ])->assertSessionHasErrors(['type', 'country_id']);

        $slider->refresh();
        $this->assertSame('unchanged.jpg', $slider->image);
        $this->assertSame(1, (int) $slider->type);
        $this->assertSame(Country::SYRIA, (int) $slider->country_id);

        $this->post(route('sliders.store'), [
            'place' => ['value' => '1'],
            'country' => ['value' => (string) Country::SYRIA],
            'image' => '',
        ])->assertSessionHasErrors(['image_path']);
        $this->assertSame(1, Slider::query()->count());
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role_id' => User::ROLE_ADMIN,
            'country_id' => Country::SYRIA,
        ]);
    }
}
