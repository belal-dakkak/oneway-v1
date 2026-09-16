<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Repositories\SettingRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
class SettingController extends Controller
{
    private $settingRepository;

    public function __construct(SettingRepository $settingRepository)
    {
        $this->settingRepository = $settingRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $data = [
            'tax_ratio'  => auth()->user()->tax_ratio,
            'enable_tax' => auth()->user()->enable_tax,
        ];

        return Inertia::render('Admin/Settings/Index', ['data' => $data]);
    }


    public function store(Request $request)
    {
        $country = auth()->user()->country_id;
        $res = $this->settingRepository->add($request,$country);
        $request->session()->flash('success', 'تم تعديل الإعدادات بنجاح');
        return Redirect::route('settings.index');
    }

    public function update_store_tax_ratio(Request $request) {

        $request->validate([
            'tax_ratio' => 'required|numeric|min:0|max:100',
            'enable_tax' => 'required|in:yes,no',
        ]);

        auth()->user()->update([
            'tax_ratio' => $request->tax_ratio,
            'enable_tax' => $request->enable_tax,
        ]);

        return response()->json(['success' => true],200);

        // $request->session()->flash('success', 'تم تعديل الإعدادات بنجاح');
        // return Redirect::route('settings.index');

    }

    public function edit($language)
    {
        $language  = $language == 'ar' ? $language : 'en';
        $country = auth()->user()->country_id;
        $settings = Setting::valuesFor((int) $country, $language);
        return Inertia::render('Admin/Settings/Edit',[
            'title' => $settings['title'] ?? '',
            'phone' => $settings['phone'] ?? '',
            'tiktok' => $settings['tiktok'] ?? '',
            'facebook' => $settings['facebook'] ?? '',
            'instagram' => $settings['instagram'] ?? '',
            'whatsapp' => $settings['whatsapp'] ?? '',
            'address' => $settings['address'] ?? '',
            'email' => $settings['email'] ?? '',
            'settinglanguage' => $language
        ]);
    }
}
