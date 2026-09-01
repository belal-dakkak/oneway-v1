<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Repositories\SettingRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Session;
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
        $country   = Session::get('country') == 'LB'?User::COUNTRY_LB:User::COUNTRY_UAE;
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
        $country   = Session::get('country') == 'LB'?User::COUNTRY_LB:User::COUNTRY_UAE;
        // $settings  = Setting::where('country',$country)->where('language',$language)->pluck('value','name')->toArray();
        $settings  = Setting::where('country',$country)->where('language',$language);
        $title     = Setting::where('country',$country)->where('language',$language)->where('name','title')->first()->value??'';
        $phone     = Setting::where('country',$country)->where('language',$language)->where('name','phone')->first()->value??'';
        $tiktok    = Setting::where('country',$country)->where('language',$language)->where('name','tiktok')->first()->value??'';
        $facebook  = Setting::where('country',$country)->where('language',$language)->where('name','facebook')->first()->value??'';
        $instagram = Setting::where('country',$country)->where('language',$language)->where('name','instagram')->first()->value??'';
        $whatsapp  = Setting::where('country',$country)->where('language',$language)->where('name','whatsapp')->first()->value??'';
        $address   = Setting::where('country',$country)->where('language',$language)->where('name','address')->first()->value??'';
        $email     = Setting::where('country',$country)->where('language',$language)->where('name','email')->first()->value??'';
        return Inertia::render('Admin/Settings/Edit',[
            'title' => $title,
            'phone' => $phone,
            'tiktok' => $tiktok,
            'facebook' => $facebook,
            'instagram' => $instagram,
            'whatsapp' => $whatsapp,
            'address' => $address,
            'email' => $email,
            'settinglanguage' => $language
        ]);
    }
}
