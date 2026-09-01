<?php

namespace App\Repositories;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
class SettingRepository
{
    public function add(Request $request,$country)
    {
        $language = $request->get('settinglanguage');
        DB::table('settings')->where('language',$language)->where('country',$country)->delete();
        $data = $request->except('_method','settinglanguage');
        foreach ($data as $key => $value) {
            Setting::create([
                'name' => $key,
                'value' => $value,
                'language' => $language,
                'country' => (int)$country
            ]);
        }

        // $setting = Setting::create([
        //     'instagram' => $request->get('instagram'),
        //     'whatsapp' => $request->get('whatsapp'),
        //     'address' => $request->get('address'),
        //     'email' => $request->get('email'),
        //     'language' => $language,
        //     'country' => $country
        // ]);
        return $country;
    }
}
