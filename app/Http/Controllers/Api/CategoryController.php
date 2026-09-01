<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\Banner;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $lang = $request->header('Accept-Language', 'en');

        $categories = Category::query()->get();

        $categoriesArray = [];
        foreach ($categories as $category) {
            if ($lang === 'en') {
                $category->name = $category->name_en;
                $category->address = $category->address_en;
            }else {
                $category->address = $category->address_ar;
            }
            $categoriesArray[] = $category;
        }

        return $this->respondSuccess($categoriesArray);
    }

    public function banners(): JsonResponse
    {
        $banners = Banner::query()->get();
        return $this->respondSuccess($banners);
    }
}
