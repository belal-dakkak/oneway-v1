<?php

namespace App\Repositories;

use App\Models\Slider;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use App\Services\StorefrontSliderService;

class SliderRepository
{
    private function clearSliderCache()
    {
        app(StorefrontSliderService::class)->clear();
    }

    public function add(array $data): Slider
    {
        $slider = Slider::query()->create([
            'image' => $data['image_path'],
            'type' => (int) $data['type'],
            'country_id' => $data['country_id'] === null ? null : (int) $data['country_id'],
        ]);
        $this->clearSliderCache();
        return $slider;
    }

    public function update(array $data, Slider $slider): Slider
    {
        $values = [
            'type' => (int) $data['type'],
            'country_id' => $data['country_id'] === null ? null : (int) $data['country_id'],
        ];
        if (!empty($data['image_path'])) {
            $values['image'] = $data['image_path'];
        }

        $slider->update($values);
        $this->clearSliderCache();
        return $slider;
    }

    public function getSliders(Request $request): LengthAwarePaginator
    {
        $sliders = Slider::query();
        if ($search = $request->get('search'))
            $sliders->where('name', 'LIKE', "%$search%");
        if ($request->has(['field', 'direction'])){
            $field = $request->get('field');
            $direction = $request->get('direction');

            $sortableArray = app(Slider::class)->getFillable();
            $sortableArray[] = 'id';
            if(in_array($field, $sortableArray)){
                $sliders->orderBy($field, $direction);
            }else{
                $sliders->orderByDesc('id');
            }
        }else{
            $sliders->orderByDesc('id');
        }

        return $sliders->paginate(10);
    }

}
