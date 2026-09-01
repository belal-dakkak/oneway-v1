<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MobileSlider;
use App\Repositories\MobileSliderRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class MobileSliderController extends Controller
{
    private $mobileSliderRepository;

    public function __construct(MobileSliderRepository $mobileSliderRepository)
    {
        $this->mobileSliderRepository = $mobileSliderRepository;
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

        $sliders = $this->mobileSliderRepository->getSliders($request);

        return Inertia::render('Admin/MobileSliders/Index', [
            'sliders' => $sliders,
            'filters' => $request->all(['search', 'field', 'direction'])
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(MobileSlider $mobileSlider)
    {
        return Redirect::route('mobile-sliders.index');
    }

    public function create(): Response
    {
        return Inertia::render('Admin/MobileSliders/Create');
    }

    public function store(Request $request)
    {
        $this->mobileSliderRepository->add($request);
        $request->session()->flash('success', 'تم إنشاء سلايدر الموبايل بنجاح');
        return Redirect::route('mobile-sliders.index');
    }

    public function edit(MobileSlider $mobileSlider): Response
    {
        $slider = transformItemForVue($mobileSlider, MobileSlider::class);
        return Inertia::render('Admin/MobileSliders/Edit', [
            'slider' => $slider,
        ]);
    }

    public function update(MobileSlider $mobileSlider, Request $request): RedirectResponse
    {
        $this->mobileSliderRepository->update($request, $mobileSlider);
        $request->session()->flash('success', 'تم تعديل سلايدر الموبايل بنجاح');
        return Redirect::route('mobile-sliders.index');
    }

    public function destroy(MobileSlider $mobileSlider, Request $request)
    {
        $mobileSlider->delete();
        \Illuminate\Support\Facades\Cache::forget('mobile_home_sliders');
        $request->session()->flash('success', 'تم حذف سلايدر الموبايل بنجاح');
        return Redirect::route('mobile-sliders.index');
    }
}
