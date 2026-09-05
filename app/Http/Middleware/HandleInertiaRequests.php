<?php

namespace App\Http\Middleware;

use App\Models\CountryCommerceSetting;
use App\Models\User;
use App\Services\CurrencyService;
use App\Support\Country;
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
        $countryCode = Country::code();
        $countryId = Country::id($countryCode);
        $currencyService = app(CurrencyService::class);
        $currencyOptions = $currencyService->optionsForCountry($countryId, true);
        $displayCurrency = $currencyService->displayForCountry($countryId);
        $commerce = CountryCommerceSetting::query()->where('country_id', $countryId)->first();

        return array_merge(parent::share($request), [
            'auth' => function() use ($request) {
                $user    = $request->user();
                $country = 2;

                if($user){
                    $credit  = $user->wallet?$user->wallet->credit:0;
                    $debit   = $user->wallet?$user->wallet->debit:0;
                    // Some historical users do not have a country assigned. Keep
                    // their dashboard usable by falling back to the active country.
                    $country = (int) ($user->country_id ?: Country::id());
                }else{
                    $credit = 0;
                    $debit  = 0;
                }
                // $country   = Session::get('country') == 'LB'?User::COUNTRY_LB:User::COUNTRY_UAE;

                try {
                    $currencyCode = Country::defaultCurrency($country);
                    $rate = app(CurrencyService::class)->rate($currencyCode);
                    $credit *= $rate;
                    $debit *= $rate;
                } catch (\InvalidArgumentException $exception) {
                    // Keep base USD balances when a local rate has not been configured yet.
                }

                return [
                    'user' => $user ?
                        [
                            'id' => $user->id,
                            'name' => $user->name,
                            'role' => $user->role_id,
                            'country_id' => $user->country_id,
                            'credit' => round($credit, $country === Country::SYRIA ? 2 : 0),
                            'debit' => round($debit, $country === Country::SYRIA ? 2 : 0),
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
            'lb_ip' => $countryCode === 'LB',
            'country' => $countryCode,
            'country_id' => $countryId,
            'countries' => Country::storefront(),
            'country_availability' => [
                'LB' => true,
                'AE' => true,
                'SY' => true,
                'TR' => false,
            ],
            'currency_options' => $currencyOptions,
            'default_currency' => Country::defaultCurrency($countryId),
            'base_currency' => Country::baseCurrency($countryId),
            'display_currency' => $displayCurrency,
            'commerce' => $commerce ? $commerce->toArray() : [
                'shipping_fee_usd' => 0,
                'free_shipping_threshold_usd' => null,
                'cod_fee_percent' => 0,
            ],
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
