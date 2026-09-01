<?php

namespace App\Repositories;

use App\Models\Slider;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SliderRepository
{
    private function clearSliderCache()
    {
        Cache::forget('home_sliders');
    }

    public function add(Request $request): Slider
    {
        $slider = null;
        if ($id = $request->get('id'))
        {
            $slider =  Slider::find($id);
            $slider->type = $request->get('place');
            if ($request->get('image') && is_array($request->get('image')))
                $slider->image = $request->get('image')[0];
            $slider->save();
        } else {
            $slider =  new Slider();
            $slider->type = $request->get('place')['value'];
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
        $slider->type = $request->get('value');
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
