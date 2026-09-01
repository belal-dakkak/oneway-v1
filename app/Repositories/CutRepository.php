<?php

namespace App\Repositories;

use App\Models\Cut;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class CutRepository
{

    public function add(Request $request): Cut
    {
        $cut = new Cut($request->all(['size', 'name', 'total']));

        $sizes = [];
        foreach ($request->get('sizes') as $size)
            $sizes[] = $size['name'];

        $colors = [];
        foreach ($request->get('colors') as $size)
            $colors[] = $size['name'];

        $cut->sizes = $sizes;
        $cut->colors = $colors;
        $cut->image = $request->get('image')[0];
        $cut->cut_date = Carbon::parse($request->get('date'));

        $cut->save();
        return $cut;
    }

    public function update(Cut $cut, Request $request)
    {
        $cut->update($request->all(['size', 'name', 'total']));

        $sizes = [];
        foreach ($request->get('sizes') as $size)
            $sizes[] = $size['name'];

        $colors = [];
        foreach ($request->get('colors') as $size)
            $colors[] = $size['name'];

        $cut->sizes = $sizes;
        $cut->colors = $colors;
        if ($request->get('image') && is_array($request->get('image')))
            $cut->image = $request->get('image')[0];
        if ($request->get('date'))
            $cut->cut_date = Carbon::parse($request->get('date'));

        $cut->save();
        return $cut;
    }

    public function getCuts(Request $request): LengthAwarePaginator
    {
        $cuts = Cut::query();

        if ($search = $request->get('search'))
            $cuts->where('name', 'LIKE', "%$search%");

        if ($request->has(['field', 'direction'])){
            $field = $request->get('field');
            $direction = $request->get('direction');

            $sortableArray = app(Cut::class)->getFillable();
            $sortableArray[] = 'id';
            if(in_array($field, $sortableArray)){
                $cuts->orderBy($field, $direction);
            }else{
                $cuts->orderByDesc('id');
            }
        }else{
            $cuts->orderByDesc('id');
        }

        return $cuts->paginate(10);
    }

}
