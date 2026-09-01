<?php

namespace App\Repositories;

use App\Models\Color;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class ColorRepository
{

    public function add(Request $request): Color
    {
        $color = new Color($request->all());
        $color->save();
        return $color;
    }

    public function update(Request $request, Color $color)
    {
        $color->update([
            'name' => $request->get('name'),
            'name_en' => $request->get('name_en'),
            'code' => $request->get('code')
        ]);
    }

    public function getColors(Request $request): LengthAwarePaginator
    {
        $colors = Color::query();

        if ($search = $request->get('search'))
            $colors->where('name', 'LIKE', "%$search%");

        if ($request->has(['field', 'direction'])){
            $field = $request->get('field');
            $direction = $request->get('direction');

            $sortableArray = app(Color::class)->getFillable();
            $sortableArray[] = 'id';
            if(in_array($field, $sortableArray)){
                $colors->orderBy($field, $direction);
            }else{
                $colors->orderByDesc('id');
            }
        }else{
            $colors->orderByDesc('id');
        }

        return $colors->paginate(10);
    }

}
