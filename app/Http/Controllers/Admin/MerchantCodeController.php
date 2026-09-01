<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MerchantCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Illuminate\Support\Str;

class MerchantCodeController extends Controller
{
    public function index(Request $request)
    {
        $codes = MerchantCode::query()
            ->when($request->search, function ($query, $search) {
                $query->where('code', 'LIKE', "%{$search}%");
            })
            ->latest()
            ->paginate(10);

        return Inertia::render('Admin/MerchantCodes/Index', [
            'codes' => $codes,
            'filters' => $request->all(['search'])
        ]);
    }

    public function generate(Request $request)
    {
        $count = $request->get('count', 1);

        for ($i = 0; $i < $count; $i++) {

            do {
                $code = random_int(10000000, 99999999);
            } while (MerchantCode::query()->where('code', $code)->exists());

            MerchantCode::query()->create([
                'code' => $code,
                'is_active' => true
            ]);
        }

        return Redirect::back()->with('success', 'تم توليد الأكواد بنجاح');
    }

    public function toggle(MerchantCode $merchantCode)
    {
        $merchantCode->update([
            'is_active' => !$merchantCode->is_active
        ]);

        return Redirect::back()->with('success', 'تم تحديث حالة الكود');
    }

    public function destroy(MerchantCode $merchantCode)
    {
        $merchantCode->delete();

        return Redirect::back()->with('success', 'تم حذف الكود بنجاح');
    }
}
