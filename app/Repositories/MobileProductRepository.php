<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class MobileProductRepository
{

    public function getProducts(Request $request, $api = false): LengthAwarePaginator
    {
        $limit = $request->get('limit', 10);
        if ($limit > 30) $limit = 30;
            $products = Product::query()->with(['category', 'colors']);

        if ($api)
            $products->has('colors');

        if ($api && $country = $request->get('country_id'))
            $products->whereIn('country_id', [$country, \App\Support\Country::globalProductId()]);

        if ($category = $request->get('category_id'))
            $products->where('category_id', $category);

        if ($search = $request->get('search')){
            $products->where('name', 'LIKE', "%$search%")
                ->orWhere('details_en', 'LIKE', "%$search%")
                ->orWhere('barcode', 'LIKE', "%$search%");
        }

        if ($maxPrice = $request->get('max_price'))
            $products->where('retail_price', '<', $maxPrice);

        if ($minPrice = $request->get('min_price'))
            $products->where('retail_price', '>', $minPrice);

        if ($colors = $request->get('colors')) {
            $products->whereHas('colors', function (Builder $query) use ($colors){
               $query->whereHas('color', function (Builder $q) use ($colors){
                   $q->whereIn('code', $colors);
               });
            });
        }

        if ($request->get('isNew')){
            $products->orderByDesc('created_at');
        }

        if ($request->has(['field', 'direction'])) {
            $field = $request->get('field');
            $direction = $request->get('direction');

            $sortableArray = app(Product::class)->getFillable();
            $sortableArray[] = 'id';
            if(in_array($field, $sortableArray)){
                $products->orderBy($field, $direction);
            }else{
                $products->orderByDesc('id');
            }
        }else{
            $products->orderByDesc('id');
        }

        return $products->paginate($limit);
    }

}
