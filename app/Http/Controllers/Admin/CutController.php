<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CutRequest;
use App\Models\Cut;
use App\Repositories\CutRepository;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class CutController extends Controller
{

    private $cutRepository;

    public function __construct(CutRepository $cutRepository)
    {
        $this->cutRepository = $cutRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $request->validate([
            'direction' => ['in:asc,desc', 'nullable'],
            'field' => ['in:stock,id,amount', 'nullable']
        ]);

        $cuts = $this->cutRepository->getCuts($request);

        return Inertia::render('Admin/Cuts/Index', [
            'cuts' => $cuts,
            'filters' => $request->all(['search', 'field', 'direction'])
        ]);
    }

    public function edit(Cut $cut): Response
    {
        $sizes = [];
        foreach ($cut->sizes as $size){
            $sizes[] = ['name' => $size];
        }
        $colors = [];
        foreach ($cut->colors as $color){
            $colors[] = ['name' => $color];
        }
        $date = Carbon::parse($cut->cut_date)->format('m/d/Y');
        $cut = transformItemForVue($cut, Cut::class);
        return Inertia::render('Admin/Cuts/Edit',[
            'cut' => $cut,
            'sizes' => $sizes,
            'colors' => $colors,
            'date' => $date,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Cuts/Create');
    }

    public function store(CutRequest $request): RedirectResponse
    {
        $this->cutRepository->add($request);
        $request->session()->flash('success', 'تم إنشاء القصة بنجاح');
        return Redirect::route('cuts.index');
    }

    public function update(Cut $cut, CutRequest $request): RedirectResponse
    {
        $this->cutRepository->update($cut, $request);
        $request->session()->flash('success', 'تم إنشاء القصة بنجاح');
        return Redirect::route('cuts.index');
    }

}
