<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccessoryRequest;
use App\Models\Accessory;
use App\Models\AccessoryLog;
use App\Models\User;
use App\Repositories\AccessoryRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class AccessoryController extends Controller
{

    private $accessoryRepository;

    public function __construct(AccessoryRepository $accessoryRepository)
    {
        $this->accessoryRepository = $accessoryRepository;
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

        $accessories = $this->accessoryRepository->getAccessories($request);

        return Inertia::render('Admin/Accessories/Index', [
            'accessories' => $accessories,
            'filters' => $request->all(['search', 'field', 'direction'])
        ]);
    }

    public function edit(Accessory $accessory): Response
    {
        $users = User::query()->where('role_id', User::ROLE_WAREHOUSE)->get();
        $users = transformDataForVue($users);

        $warehouse = transformItemForVue($accessory->warehouse, User::class);

        $accessory = transformItemForVue($accessory, Accessory::class);
        return Inertia::render('Admin/Accessories/Edit',[
            'accessory' => $accessory,
            'users' => $users,
            'warehouse' => $warehouse
        ]);
    }

    public function create(): Response
    {
        $users = User::query()->where('role_id', User::ROLE_WAREHOUSE)->get();
        $users = transformDataForVue($users);
        return Inertia::render('Admin/Accessories/Create', [
            'users' => $users
        ]);
    }

    public function store(AccessoryRequest $request): RedirectResponse
    {
        $this->accessoryRepository->add($request);
        $request->session()->flash('success', 'تم إنشاء القصة بنجاح');
        return Redirect::route('accessories.index');
    }

    public function update(Accessory $accessory, AccessoryRequest $request): RedirectResponse
    {
        $this->accessoryRepository->update($accessory, $request);
        $request->session()->flash('success', 'تم إنشاء القصة بنجاح');
        return Redirect::route('accessories.index');
    }

    public function logs(Accessory $accessory): Response
    {
        $logs = AccessoryLog::query()->with(['user', 'accessory'])->where('accessory_id', $accessory->id)->get();
        $logs = transformDataForVue($logs);

        return Inertia::render('Admin/Accessories/Logs', [
            'logs' => $logs
        ]);
    }

    public function exports(Accessory $accessory, Request $request)
    {
        $exports = $request->get('exports');
        if ($exports > $accessory->count)
            return response()->json([
               'error' => 'القيمة المدخلة أكبر من إجمالي الياردات'
            ]);
        $this->accessoryRepository->update($accessory, $request);

        return response()->json([
            'success' => true
        ]);
    }

}
