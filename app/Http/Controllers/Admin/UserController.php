<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\UserRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller

{

    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
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
        if(auth()->user()->country_id == 2) $rate = 3.675;
        else $rate = 1;
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
        $user = User::query()->findOrFail($id);
        $amount = $user->wallet->credit;
        Wallet::query()->updateOrCreate([
            'user_id' => $id
        ],[
           'credit' => 0,
            'user_id' => $id
        ]);

        $warehouse = User::query()->findOrFail(auth()->id());
        $oldAmount = $warehouse->wallet?$warehouse->wallet->credit:0;
        Wallet::query()->updateOrCreate([
            'user_id' => auth()->id()
        ],[
           'credit' => $amount + $oldAmount,
            'user_id' => auth()->id()
        ]);
        return Redirect::route('users.index', ['type' => User::ROLE_SHOP]);
    }
	
	public function show(User $user): Response
    {
        return Inertia::render('Admin/Users/Show', ['user' => $user]);
    }


}
