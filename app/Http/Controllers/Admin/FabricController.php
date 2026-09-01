<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FabricRequest;
use App\Models\Accessory;
use App\Models\AccessoryLog;
use App\Models\Fabric;
use App\Models\FabricLog;
use App\Models\User;
use App\Repositories\FabricRepository;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class FabricController extends Controller
{

    private $fabricRepository;

    public function __construct(FabricRepository $fabricRepository)
    {
        $this->fabricRepository = $fabricRepository;
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

        $fabrics = $this->fabricRepository->getFabrics($request);

        return Inertia::render('Admin/Fabrics/Index', [
            'fabrics' => $fabrics,
            'filters' => $request->all(['search', 'field', 'direction'])
        ]);
    }

    public function edit(Fabric $fabric): Response
    {
        $users = User::query()->where('role_id', User::ROLE_WAREHOUSE)->get();
        $users = transformDataForVue($users);

        $warehouse = transformItemForVue($fabric->warehouse, User::class);

        $fabric = transformItemForVue($fabric, Fabric::class);
        return Inertia::render('Admin/Fabrics/Edit',[
            'fabric' => $fabric,
            'users' => $users,
            'warehouse' => $warehouse
        ]);
    }

    public function create(): Response
    {
        $users = User::query()->where('role_id', User::ROLE_WAREHOUSE)->get();
        $users = transformDataForVue($users);
        return Inertia::render('Admin/Fabrics/Create', [
            'users' => $users
        ]);
    }

    public function store(FabricRequest $request): RedirectResponse
    {
        $this->fabricRepository->add($request);
        $request->session()->flash('success', 'تم إنشاء القصة بنجاح');
        return Redirect::route('fabrics.index');
    }

    public function update(Fabric $fabric, FabricRequest $request): RedirectResponse
    {
        $this->fabricRepository->update($fabric, $request);
        $request->session()->flash('success', 'تم إنشاء القصة بنجاح');
        return Redirect::route('fabrics.index');
    }

    public function exports(Fabric $fabric, Request $request)
    {
        $exports = $request->get('exports');
        if ($exports > $fabric->yards)
            return response()->json([
               'error' => 'القيمة المدخلة أكبر من إجمالي الياردات'
            ]);
        $this->fabricRepository->update($fabric, $request);

        return response()->json([
            'success' => true
        ]);
    }

    public function logs(Fabric $fabric): Response
    {
        $logs = FabricLog::query()->with(['user', 'fabric'])->where('fabric_id', $fabric->id)->get();
        $logs = transformDataForVue($logs);

        return Inertia::render('Admin/Fabrics/Logs', [
            'logs' => $logs
        ]);
    }


}
