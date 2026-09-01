<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\ExpenseRepository;
use App\Services\CurrencyService;
use App\Support\Country;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller

{

    private $expenseRepository;

    public function __construct(ExpenseRepository $expenseRepository)
    {
        $this->expenseRepository = $expenseRepository;
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

        $expenses = $this->expenseRepository->getExpenses($request);

        return Inertia::render('Admin/Expenses/Index', [
            'expenses' => $expenses,
            'filters' => $request->all(['search', 'field', 'direction'])
        ]);
    }

    public function create(): Response
    {
        if (auth()->user()->role_id === User::ROLE_WAREHOUSE || auth()->user()->role_id === User::ROLE_ADMIN)
            $users = User::query()->where('role_id', User::ROLE_MERCHANT)->get();
        else
            $users = [];

        return Inertia::render('Admin/Expenses/Create', [
            'users' => $users
        ]);
    }

    public function store(ExpenseRequest $request): RedirectResponse
    {
        $oldCredit = auth()->user()->wallet->credit;
        $rate = app(CurrencyService::class)->rate(Country::defaultCurrency(auth()->user()->country_id));
        $amount = $request->get('amount') / $rate;
        Wallet::query()->updateOrCreate(
            ['user_id' => auth()->id()],
            ['credit' => $oldCredit - $amount, 'user_id' => auth()->id()]
        );

        $this->expenseRepository->add($request);
        $request->session()->flash('success', 'تم إنشاء الدفعة بنجاح');
        return Redirect::route('expenses.index');
    }

}
