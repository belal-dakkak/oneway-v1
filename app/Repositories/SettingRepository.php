<?php

namespace App\Repositories;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
class SettingRepository
{
    public function add(Request $request,$country)
    {
        $language = $request->get('settinglanguage');
        $data = $request->except('_method', '_token', 'settinglanguage');
        $keyColumn = Setting::keyColumn();
        DB::transaction(function () use ($language, $country, $data, $keyColumn) {
            DB::table('settings')->where('language', $language)->where('country', $country)->delete();
            foreach ($data as $key => $value) {
                Setting::create([
                    $keyColumn => $key,
                    'value' => $value,
                    'language' => $language,
                    'country' => (int) $country,
                ]);
            }
        });
        Cache::forget("shop_settings_{$country}_{$language}");

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
