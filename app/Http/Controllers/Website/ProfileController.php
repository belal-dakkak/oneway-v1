<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use App\Models\WebsiteOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(): Response
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $categories = Category::limit(6)->get();
        $categories = transformDataForVue($categories);

        $country = Session::get('country') == 'LB' ? User::COUNTRY_LB : User::COUNTRY_UAE;
        $language = 'en';
        $settings = Setting::where('country', $country)->where('language', $language)->pluck('value', 'name')->toArray();

        $countryId = Session::get('country') == 'LB' ? 1 : 2;
        
        // Fetch Orders
        $orders = WebsiteOrder::where('buyer_id', $user->id)
            ->with(['items.product.product'])
            ->latest()
            ->get();
        $orders = transformDataForVue($orders);

        // Fetch Favorites
        $favorites = $user->wishList()
            ->whereIn('products.country_id', [$countryId, 3])
            ->when(Session::get('is_merchant'), fn($q) => $q->where('shown_for_merchant', true))
            ->whereHas('colors.userProducts', function($q) use ($countryId) {
                $q->where('country_id', $countryId)->where('stock', '>', 0);
            })
            ->with(['colors' => function($q) {
                $q->with('userProducts');
            }])
            ->get();
        $favorites = transformDataForVue($favorites);

        return Inertia::render('Website/Profile', [
            'user' => $user,
            'orders' => $orders,
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

    public function update(Request $request)
    {
        $user = auth()->user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
        ]);

        $user->update($request->only('name', 'email', 'phone', 'address'));

        return back()->with('success', 'Profile updated successfully');
    }
}
