<?php

namespace App\Http\Middleware;

use App\Models\CountryCommerceSetting;
use App\Models\User;
use App\Services\CurrencyService;
use App\Services\SalesCurrencyPolicy;
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
        $defaultStorefrontCurrency = Country::defaultCurrency($countryId);
        try {
            $currencyService->rate('SYP');
            $syriaRetailAvailable = true;
        } catch (\InvalidArgumentException $exception) {
            $syriaRetailAvailable = false;
        }
        if ($countryId === Country::SYRIA) {
            try {
                $currency = app(SalesCurrencyPolicy::class)
                    ->websiteOption($countryId, (bool) Session::get('is_merchant'));
                $defaultStorefrontCurrency = $currency['code'];
            } catch (\InvalidArgumentException $exception) {
                $currencyOptions = array_values(array_filter($currencyOptions, function ($option) {
                    return ($option['code'] ?? null) === 'USD';
                }));
                $syriaRetailAvailable = (bool) Session::get('is_merchant');
            }
        }
        $displayCurrency = $currencyService->displayForCountry($countryId);
        $commerce = CountryCommerceSetting::query()->where('country_id', $countryId)->first();

        return array_merge(parent::share($request), [
            'auth' => function() use ($request) {
                $user    = $request->user();
                $country = 2;

                if($user){
                    $country = (int) ($user->country_id ?: Country::id());
                    $defaultCurrency = Country::defaultCurrency($country);
                    $wallets = $user->wallets()->get();
                    $defaultWallet = $wallets->first(function ($wallet) use ($defaultCurrency) {
                        return strtoupper((string) ($wallet->currency_code ?: 'USD')) === $defaultCurrency;
                    });
                    $credit = (float) ($defaultWallet->credit ?? 0);
                    $debit = (float) ($defaultWallet->debit ?? 0);
                    $cashboxes = $wallets
                        ->mapWithKeys(function ($wallet) {
                            $code = strtoupper((string) ($wallet->currency_code ?: 'USD'));
                            return [$code => [
                                'currency' => $code,
                                'credit' => (float) $wallet->credit,
                                'debit' => (float) $wallet->debit,
                                'balance' => (float) $wallet->credit - (float) $wallet->debit,
                            ]];
                        });
                }else{
                    $credit = 0;
                    $debit  = 0;
                    $cashboxes = collect();
                }
                // $country   = Session::get('country') == 'LB'?User::COUNTRY_LB:User::COUNTRY_UAE;

                return [
                    'user' => $user ?
                        [
                            'id' => $user->id,
                            'name' => $user->name,
                            'role' => $user->role_id,
                            'country_id' => $user->country_id,
                            // Legacy consumers receive the country's real default
                            // cashbox instead of a converted USD wallet.
                            'credit' => round($credit, $defaultCurrency === 'SYP' ? 0 : 2),
                            'debit' => round($debit, $defaultCurrency === 'SYP' ? 0 : 2),
                            'cashboxes' => $cashboxes,
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
                // Retail needs a valid SYP rate. An already verified merchant can
                // still enter Syria because wholesale checkout is always in USD.
                'SY' => $syriaRetailAvailable || (bool) Session::get('is_merchant'),
                'TR' => false,
            ],
            'currency_options' => $currencyOptions,
            'default_currency' => $defaultStorefrontCurrency,
            'transaction_currency' => $defaultStorefrontCurrency,
            'base_currency' => Country::baseCurrency($countryId),
            'display_currency' => $displayCurrency,
            'commerce' => $commerce ? array_merge($commerce->toArray(), [
                'card_available' => $commerce->cardIsAvailable(),
            ]) : [
                'shipping_fee_usd' => 0,
                'free_shipping_threshold_usd' => null,
                'cod_fee_percent' => 0,
                'card_available' => false,
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
