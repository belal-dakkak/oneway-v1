<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Color;
use App\Repositories\ColorRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ColorController extends Controller

{

    private $colorRepository;

    public function __construct(ColorRepository $colorRepository)
    {
        $this->colorRepository = $colorRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $request->validate([
            'direction' => ['in:asc,desc', 'nullable'],
            'field' => ['in:stock,id,color_id', 'nullable']
        ]);

        $colors = $this->colorRepository->getColors($request);

        return Inertia::render('Admin/Colors/Index', [
            'colors' => $colors,
            'filters' => $request->all(['search', 'field', 'direction'])
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Color $color)
    {
        return Redirect::route('colors.index');
    }

    public function create(): Response
    {

        return Inertia::render('Admin/Colors/Create');
    }

    public function store(Request $request)
    {
        $this->colorRepository->add($request);
        $request->session()->flash('success', 'تم إنشاء اللون بنجاح');
        return Redirect::route('colors.index');
    }

    public function edit(Color $color): Response
    {
        $color = transformItemForVue($color, Color::class);
        return Inertia::render('Admin/Colors/Edit',[
            'color' => $color
        ]);
    }

    public function update(Color $color, Request $request): RedirectResponse
    {
        $this->colorRepository->update($request, $color);
        $request->session()->flash('success', 'تم تعديل اللون بنجاح');
        return Redirect::route('colors.index');
    }

}
