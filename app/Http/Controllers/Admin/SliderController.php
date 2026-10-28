<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SliderRequest;
use App\Models\Slider;
use App\Repositories\SliderRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\StorefrontSliderService;
use App\Support\Country;
class SliderController extends Controller
{
    private $sliderRepository;

    public function __construct(SliderRepository $sliderRepository)
    {
        $this->sliderRepository = $sliderRepository;
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

        $sliders = $this->sliderRepository->getSliders($request);

        return Inertia::render('Admin/Sliders/Index', [
            'sliders' => $sliders,
            'filters' => $request->all(['search', 'field', 'direction'])
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Slider $slider)
    {
        return Redirect::route('sliders.index');
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Sliders/Create', ['countries' => $this->countryOptions()]);
    }

    public function store(SliderRequest $request)
    {
        $this->sliderRepository->add($request->validated());
        $request->session()->flash('success', 'تم إنشاء السلايدر بنجاح');
        return Redirect::route('sliders.index');
    }

    public function edit(Slider $slider): Response
    {

        $slider = transformItemForVue($slider, Slider::class);
        return Inertia::render('Admin/Sliders/Edit',[
            'slider' => $slider,
            'countries' => $this->countryOptions(),
        ]);
    }

    public function update(Slider $slider, SliderRequest $request): RedirectResponse
    {
        $this->sliderRepository->update($request->validated(), $slider);
        $request->session()->flash('success', 'تم تعديل السلايدر بنجاح');
        return Redirect::route('sliders.index');
    }

    public function destroy(Slider $slider, Request $request)
    {
        $slider->delete();
        app(StorefrontSliderService::class)->clear();
        $request->session()->flash('success', 'تم حذف السلايدر بنجاح');
        return Redirect::route('sliders.index');

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
