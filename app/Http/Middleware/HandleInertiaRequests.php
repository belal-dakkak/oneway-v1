<?php

namespace App\Http\Middleware;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => function() use ($request) {
                $user    = $request->user();
                $country = 2;

                if($user){
                    $credit  = $user->wallet?$user->wallet->credit:0;
                    $debit   = $user->wallet?$user->wallet->debit:0;
                    $country = $user->country_id;
                }else{
                    $credit = 0;
                    $debit  = 0;
                }
                // $country   = Session::get('country') == 'LB'?User::COUNTRY_LB:User::COUNTRY_UAE;

                if($country == 2){
                    $currs   = Currency::where('name','aed')->first();
                    if ($currs) {
                        $credit *= $currs->rate;
                        $debit  *= $currs->rate;
                    }
                }

                return [
                    'user' => $user ?
                        [
                            'id' => $user->id,
                            'name' => $user->name,
                            'role' => $user->role_id,
                            'country_id' => $user->country_id,
                            'credit' => round($credit),
                            'debit' => round($debit),
                            'notifications' => $user->notifications()->orderByDesc('created_at')->limit(5)->get(),
                            'notifications_count' => $user->unreadNotifications()->count(),
                        ]
                        : null,
                ];
            },
            'flash' => function () use ($request) {
                return [
                    'success' => $request->session()->get('success'),
                    'success_delete' => $request->session()->get('success_delete'),
                    'server_error' => $request->session()->get('error'),
                ];
            },
            'popstate' => false,
            'lb_ip' => (boolean) Session::get('country') == 'LB',
            'country' => Session::get('country') ?? 'AE',
            'isMerchant' => (boolean) Session::get('is_merchant'),
            'locale' => function () {
                return app()->getLocale();
            },
            'language' => function () {
                return translations(
                    resource_path('lang/'. app()->getLocale() .'.json')
                );
            },
        ]);
    }
}
