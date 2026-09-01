<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Product;
use App\Repositories\BannerRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class BannerController extends Controller

{

    private $bannerRepository;

    public function __construct(BannerRepository $bannerRepository)
    {
        $this->bannerRepository = $bannerRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $request->validate([
            'direction' => ['in:asc,desc', 'nullable'],
            'field' => ['in:id,name', 'nullable']
        ]);

        $banners = $this->bannerRepository->getBanners($request);

        return Inertia::render('Admin/Banners/Index', [
            'banners' => $banners,
            'filters' => $request->all(['search', 'field', 'direction'])
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Banner $banner)
    {
        return Redirect::route('banners.index');
    }

    public function create(): Response
    {
        $products = Product::query()->get();
        return Inertia::render('Admin/Banners/Create', [
            'products' => $products
        ]);
    }

    public function store(Request $request)
    {
        $this->bannerRepository->add($request);
        $request->session()->flash('success', 'تم إنشاء السلايدر بنجاح');
        return Redirect::route('banners.index');
    }

    public function edit(Banner $banner): Response
    {
        $products = Product::query()->get();

        $banner = transformItemForVue($banner, Banner::class);
        return Inertia::render('Admin/Banners/Edit',[
            'banner' => $banner,
            'products' => $products
        ]);
    }

    public function update(Banner $banner, Request $request): RedirectResponse
    {
        $this->bannerRepository->update($request, $banner);
        $request->session()->flash('success', 'تم تعديل السلايدر بنجاح');
        return Redirect::route('banners.index');
    }

    public function destroy(Banner $banner, Request $request)
    {
        $banner->delete();
        $request->session()->flash('success', 'تم حذف السلايدر بنجاح');
        return Redirect::route('banners.index');

    }


}
