<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchRequest;
use App\Models\Branch;
use App\Models\Product;
use App\Repositories\BranchRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
class BranchController extends Controller
{
    private $branchRepository;
    public function __construct(BranchRepository $branchRepository)
    {
        $this->branchRepository = $branchRepository;
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

        $branches = $this->branchRepository->getBranches($request);

        return Inertia::render('Admin/Branches/Index', [
            'branches' => $branches,
            'filters' => $request->all(['search', 'field', 'direction'])
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Branch $branch)
    {
        return Redirect::route('branches.index');
    }

    public function create(): Response
    {
        $products = Product::query()->get();
        return Inertia::render('Admin/Branches/Create', [
            'products' => $products
        ]);
    }

    public function store(BranchRequest $request): RedirectResponse
    {
        $this->branchRepository->add($request);
        $request->session()->flash('success', 'تم إنشاء الفرع بنجاح');
        return Redirect::route('branches.index');
    }

    public function edit(Branch $branch): Response
    {
        $products = Product::query()->get();

        $branch = transformItemForVue($branch, Branch::class);
        return Inertia::render('Admin/Branches/Edit',[
            'branch' => $branch,
            'products' => $products
        ]);
    }

    public function update(Branch $branch, BranchRequest $request): RedirectResponse
    {
        $this->branchRepository->update($request, $branch);
        $request->session()->flash('success', 'تم تعديل الفرع بنجاح');
        return Redirect::route('branches.index');
    }

    public function destroy(Branch $branch, Request $request)
    {
        $branch->delete();
        $request->session()->flash('success', 'تم حذف الفرع بنجاح');
        return Redirect::route('branches.index');

    }

}
