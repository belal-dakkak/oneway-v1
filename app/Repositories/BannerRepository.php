<?php

namespace App\Repositories;

use App\Models\Banner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class BannerRepository
{

    public function add(Request $request): Banner
    {
        $banner =  new Banner([
            'related_id' => $request->get('selected_product')['id'],
            'type' => Banner::TYPE_PRODUCT
        ]);
        if ($request->get('image') && is_array($request->get('image')))
            $banner->image = $request->get('image')[0];

        $banner->save();
        return $banner;
    }

    public function update(Request $request, Banner $banner)
    {
        $banner->update([
            'related_id' => $request->get('selected_product')['id']
        ]);
        if ($request->get('image') && is_array($request->get('image')))
            $banner->image = $request->get('image')[0];
        $banner->save();
    }

    public function getBanners(Request $request): LengthAwarePaginator
    {
        $banners = Banner::query();

        if ($search = $request->get('search'))
            $banners->where('name', 'LIKE', "%$search%");

        if ($request->has(['field', 'direction'])){
            $field = $request->get('field');
            $direction = $request->get('direction');

            $sortableArray = app(Banner::class)->getFillable();
            $sortableArray[] = 'id';
            if(in_array($field, $sortableArray)){
                $banners->orderBy($field, $direction);
            }else{
                $banners->orderByDesc('id');
            }
        }else{
            $banners->orderByDesc('id');
        }

        return $banners->paginate(10);
    }

}
