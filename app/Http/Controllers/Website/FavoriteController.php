<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Support\Country;
use Inertia\Inertia;
use Inertia\Response;

class FavoriteController extends Controller
{
    public function index(): Response
    {
        $categories = Category::limit(6)->get();
        $categories = transformDataForVue($categories);

        $country = Country::id();
        $language = 'en';
        $settings = Setting::where('country', $country)->where('language', $language)->pluck('value', 'name')->toArray();

        $favorites = [];
        if (auth()->check()) {
            $countryId = Country::id();
            $favorites = auth()->user()->wishList()
                ->whereIn('products.country_id', [$countryId, \App\Support\Country::globalProductId()])
                ->when(Session::get('is_merchant'), fn($q) => $q->where('shown_for_merchant', true))
                ->whereHas('colors.userProducts', function($q) use ($countryId) {
                    $q->where('country_id', $countryId)->where('stock', '>', 0);
                })
                ->with('colors')
                ->get();
            $favorites = transformDataForVue($favorites);
        }

        return Inertia::render('Favorites', [
            'favorites' => $favorites,
            'categories' => $categories,
            'phone' => $settings['phone'] ?? '',
            'email' => $settings['email'] ?? '',
            'facebook' => $settings['facebook'] ?? '',
            'instagram' => $settings['instagram'] ?? '',
            'tiktok' => $settings['tiktok'] ?? '',
            'address' => $settings['address'] ?? '',
        ]);
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = auth()->user();
        $productId = $request->product_id;

        if (Favorite::where('product_id', $productId)->where('user_id', $user->id)->exists()) {
            $user->wishList()->detach($productId);
            return response()->json(['added' => false, 'message' => 'Removed from favorites']);
        } else {
            $user->wishList()->attach($productId);
            return response()->json(['added' => true, 'message' => 'Added to favorites']);
        }
    }
}
