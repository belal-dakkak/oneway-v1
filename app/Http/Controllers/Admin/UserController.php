<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\CashboxService;
use App\Services\CurrencyService;
use App\Support\Country;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller

{

    private $userRepository;
    private $cashboxes;

    public function __construct(UserRepository $userRepository, CashboxService $cashboxes)
    {
        $this->userRepository = $userRepository;
        $this->cashboxes = $cashboxes;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'direction' => ['in:asc,desc', 'nullable'],
            'type' => ['in:1,2,3,4,5,6', 'required'],
            'field' => ['in:stock,id,user_id', 'nullable']
        ]);

        $users = $this->userRepository->getUsers($request);

        if ($request->wantsJson()){
            return $users;
        }
        $rate = app(CurrencyService::class)->rate(Country::defaultCurrency(auth()->user()->country_id));
        return Inertia::render('Admin/Users/Index', [
            'rate' => $rate,
            'users' => $users,
            'type' => $request->get('type'),
            'filters' => $request->all(['search', 'field', 'direction'])
        ]);
    }

    public function create(Request $request): Response
    {
        $countries = getAvailableCountries();
        return Inertia::render('Admin/Users/Create', ['type' => $request->get('type'),'countries' => $countries]);
    }

    public function store(UserRequest $request)
    {
       $this->userRepository->add($request);
        $request->session()->flash('success', 'تم إنشاء الحساب بنجاح');
        return Redirect::route('users.index', ['type' => $request->get('role_id')]);
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Edit', ['user' => $user]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $this->userRepository->update($request, $user);
        $request->session()->flash('success', 'تم تعديل الحساب بنجاح');
        return Redirect::route('users.index', ['type' => $user->role_id]);
    }

    public function destroy(Request $request,User $user): RedirectResponse
    {
        $type = $user->role_id;
        $user->delete();
        $request->session()->flash('success', 'تم حذف الحساب بنجاح');
        return Redirect::route('users.index', ['type' => $type]);
    }

    public function closeWallet($id): RedirectResponse
    {
        $receiverId = (int) auth()->id();

        DB::transaction(function () use ($id, $receiverId) {
            abort_if((int) $id === $receiverId, 422, 'The source and destination cashboxes must be different.');

            // Lock in a deterministic order so two simultaneous closures cannot deadlock.
            $users = User::query()
                ->whereIn('id', [(int) $id, $receiverId])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $source = $users->get((int) $id);
            $receiver = $users->get($receiverId);
            abort_unless($source && $receiver, 404);

            abort_unless(
                (int) $source->country_id === (int) $receiver->country_id &&
                in_array((int) $source->role_id, [User::ROLE_SHOP, User::ROLE_WAREHOUSE], true),
                403
            );

            $wallets = $source->wallets()->lockForUpdate()->get();
            $group = (string) Str::uuid();
            foreach ($wallets as $wallet) {
                $amount = round((float) $wallet->credit - (float) $wallet->debit, 4);
                if ($amount <= 0) {
                    continue;
                }

                $currency = strtoupper((string) $wallet->currency_code);
                $context = [
                    'payment_method' => 'sales_closure',
                    'source_type' => User::class,
                    'source_id' => (int) $source->id,
                    'exchange_group' => $group,
                    'note' => "Sales closure from {$source->name} to {$receiver->name}",
                ];
                $this->cashboxes->debit(
                    (int) $source->id,
                    $amount,
                    $currency,
                    "sales-close:{$group}:{$currency}:source",
                    $context
                );
                $this->cashboxes->credit(
                    (int) $receiver->id,
                    $amount,
                    $currency,
                    "sales-close:{$group}:{$currency}:receiver",
                    $context
                );
            }
        }, 3);

        return Redirect::route('users.index', ['type' => User::ROLE_SHOP]);
    }
	
	public function show(User $user): Response
    {
        return Inertia::render('Admin/Users/Show', ['user' => $user]);
    }


}
