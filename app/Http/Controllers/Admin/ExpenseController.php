<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use App\Models\User;
use App\Repositories\ExpenseRepository;
use App\Services\CurrencyService;
use App\Services\CashboxService;
use App\Support\Country;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller

{

    private $expenseRepository;
    private $cashboxes;

    public function __construct(ExpenseRepository $expenseRepository, CashboxService $cashboxes)
    {
        $this->expenseRepository = $expenseRepository;
        $this->cashboxes = $cashboxes;
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
        DB::transaction(function () use ($request) {
            $expense = $this->expenseRepository->add($request);
            $localCurrency = Country::defaultCurrency((int) auth()->user()->country_id);
            $rate = app(CurrencyService::class)->rate($localCurrency);
            $cashboxAmount = $localCurrency === 'USD'
                ? (float) $request->get('amount')
                : app(CurrencyService::class)->toUsdAtRate((float) $request->get('amount'), $rate);
            $this->cashboxes->debit(
                (int) auth()->id(),
                $cashboxAmount,
                'USD',
                "expense:{$expense->id}",
                [
                    'exchange_rate' => 1,
                    'payment_method' => 'cash',
                    'source_type' => Expense::class,
                    'source_id' => $expense->id,
                    'note' => $expense->description,
                ]
            );
        }, 3);
        $request->session()->flash('success', 'تم إنشاء الدفعة بنجاح');
        return Redirect::route('expenses.index');
    }

}
