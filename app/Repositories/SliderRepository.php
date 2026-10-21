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

    private function countryId(Request $request): ?int
    {
        $country = $request->input('country');
        $value = is_array($country) ? ($country['id'] ?? $country['value'] ?? null) : $country;
        return $value === null || $value === '' ? null : (int) $value;
    }

    public function add(Request $request): Slider
    {
        $slider = null;
        if ($id = $request->get('id'))
        {
            $slider =  Slider::find($id);
            $place = $request->get('place');
            $slider->type = is_array($place) ? $place['value'] : $place;
            $slider->country_id = $this->countryId($request);
            if ($request->get('image') && is_array($request->get('image')))
                $slider->image = $request->get('image')[0];
            $slider->save();
        } else {
            $slider =  new Slider();
            $slider->type = $request->get('place')['value'];
            $slider->country_id = $this->countryId($request);
            if ($request->get('image') && is_array($request->get('image')))
                $slider->image = $request->get('image')[0];
            $slider->save();
        }
        $this->clearSliderCache();
        return $slider;
    }

    public function update(Request $request, Slider $slider)
    {
        if ($request->get('image') && is_array($request->get('image')))
            $slider->image = $request->get('image')[0];
        $place = $request->get('place', $request->get('value'));
        $slider->type = is_array($place) ? $place['value'] : $place;
        $slider->country_id = $this->countryId($request);
        $slider->save();
        $this->clearSliderCache();
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
