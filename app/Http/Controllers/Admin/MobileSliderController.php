<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SliderRequest;
use App\Models\MobileSlider;
use App\Repositories\MobileSliderRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\StorefrontSliderService;
use App\Support\Country;

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
        return Inertia::render('Admin/MobileSliders/Create', ['countries' => $this->countryOptions()]);
    }

    public function store(SliderRequest $request)
    {
        $this->mobileSliderRepository->add($request->validated());
        $request->session()->flash('success', 'تم إنشاء سلايدر الموبايل بنجاح');
        return Redirect::route('mobile-sliders.index');
    }

    public function edit(MobileSlider $mobileSlider): Response
    {
        $slider = transformItemForVue($mobileSlider, MobileSlider::class);
        return Inertia::render('Admin/MobileSliders/Edit', [
            'slider' => $slider,
            'countries' => $this->countryOptions(),
        ]);
    }

    public function update(MobileSlider $mobileSlider, SliderRequest $request): RedirectResponse
    {
        $this->mobileSliderRepository->update($request->validated(), $mobileSlider);
        $request->session()->flash('success', 'تم تعديل سلايدر الموبايل بنجاح');
        return Redirect::route('mobile-sliders.index');
    }

    public function destroy(MobileSlider $mobileSlider, Request $request)
    {
        $mobileSlider->delete();
        app(StorefrontSliderService::class)->clear();
        $request->session()->flash('success', 'تم حذف سلايدر الموبايل بنجاح');
        return Redirect::route('mobile-sliders.index');
    }

    private function countryOptions(): array
    {
        $options = [['name' => 'All Countries', 'value' => '']];
        foreach (Country::storefront() as $country) {
            $options[] = ['name' => $country['name'], 'value' => (string) $country['id']];
        }
        return $options;
    }
}
