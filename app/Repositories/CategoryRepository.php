<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryRepository
{
    private function clearCategoryCache()
    {
        Cache::forget('shop_categories');
    }

    public function add(Request $request): Category
    {
        $category = new Category($request->all());
        if ($request->get('image') && is_array($request->get('image')))
            $category->image = $request->get('image')[0];

        $category->save();
        $this->clearCategoryCache();
        return $category;
    }

    public function update(Request $request, Category $category)
    {
        $category->update($request->all());
        if ($request->get('image') && is_array($request->get('image')))
            $category->image = $request->get('image')[0];
        $category->save();
        $this->clearCategoryCache();
    }

    public function getCategories(Request $request): LengthAwarePaginator
    {
        $categories = Category::query();

        if ($search = $request->get('search'))
            $categories->where('name', 'LIKE', "%$search%");

        if ($request->has(['field', 'direction'])){
            $field = $request->get('field');
            $direction = $request->get('direction');

            $sortableArray = app(Category::class)->getFillable();
            $sortableArray[] = 'id';
            if(in_array($field, $sortableArray)){
                $categories->orderBy($field, $direction);
            }else{
                $categories->orderByDesc('id');
            }
        }else{
            $categories->orderByDesc('id');
        }

        return $categories->paginate(40);
    }

}
