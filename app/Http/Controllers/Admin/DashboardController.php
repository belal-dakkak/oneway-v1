<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $categories_ids = Product::where('country_id',auth()->user()->country_id)->pluck('category_id')->toArray();
        $categories = Category::query()->withCount(['colors','products'])->whereIn('id',$categories_ids)->get();
        return Inertia::render('Dashboard', [
            'categories' => $categories,
        ]);
    }
}
