<?php

namespace App\Repositories;

use App\Models\MobileSlider;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MobileSliderRepository
{
    private function clearSliderCache()
    {
        Cache::forget('mobile_home_sliders');
    }

    public function add(Request $request): MobileSlider
    {
        $slider = null;
        if ($id = $request->get('id'))
        {
            $slider =  MobileSlider::find($id);
            $slider->type = $request->get('place');
            if ($request->get('image') && is_array($request->get('image')))
                $slider->image = $request->get('image')[0];
            $slider->save();
        } else {
            $slider =  new MobileSlider();
            $slider->type = $request->get('place')['value'];
            if ($request->get('image') && is_array($request->get('image')))
                $slider->image = $request->get('image')[0];
            $slider->save();
        }
        $this->clearSliderCache();
        return $slider;
    }

    public function update(Request $request, MobileSlider $slider)
    {
        if ($request->get('image') && is_array($request->get('image')))
            $slider->image = $request->get('image')[0];
        $slider->type = $request->get('value');
        $slider->save();
        $this->clearSliderCache();
    }

    public function getSliders(Request $request): LengthAwarePaginator
    {
        $sliders = MobileSlider::query();
        if ($search = $request->get('search'))
            $sliders->where('name', 'LIKE', "%$search%");
        if ($request->has(['field', 'direction'])){
            $field = $request->get('field');
            $direction = $request->get('direction');

            $sortableArray = app(MobileSlider::class)->getFillable();
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
