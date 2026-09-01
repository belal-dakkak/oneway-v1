<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller

{

    private $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
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

        $categories = $this->categoryRepository->getCategories($request);

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
            'filters' => $request->all(['search', 'field', 'direction'])
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return Redirect::route('categories.index');
    }

    public function create(): Response
    {

        return Inertia::render('Admin/Categories/Create');
    }

    public function store(Request $request)
    {
        $this->categoryRepository->add($request);
        $request->session()->flash('success', 'تم إنشاء الصنف بنجاح');
        return Redirect::route('categories.index');
    }

    public function edit(Category $category): Response
    {
        $category = transformItemForVue($category, Category::class);
        return Inertia::render('Admin/Categories/Edit',[
            'category' => $category
        ]);
    }

    public function update(Category $category, Request $request): RedirectResponse
    {
        $this->categoryRepository->update($request, $category);
        $request->session()->flash('success', 'تم تعديل الصنف بنجاح');
        return Redirect::route('categories.index');
    }


}
