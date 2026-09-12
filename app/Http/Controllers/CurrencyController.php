<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\CountryCommerceSetting;
use App\Services\CurrencyService;
use App\Support\Country;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
class CurrencyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): Response
    {

        return Inertia::render('Admin/Currencies/Edit',[
            'lp'=> optional(Currency::where('name','lp')->first())->rate,
            'aed'=> optional(Currency::where('name','aed')->first())->rate,
            'syp'=> optional(Currency::where('name','syp')->first())->rate,
            'commerceSettings' => collect(Country::allowedIds())->mapWithKeys(function ($countryId) {
                return [$countryId => CountryCommerceSetting::forCountry($countryId)];
            }),
            'cashboxUsers' => User::query()
                ->whereIn('role_id', [User::ROLE_SHOP, User::ROLE_WAREHOUSE])
                ->whereIn('country_id', Country::allowedIds())
                ->orderBy('name')
                ->get(['id', 'name', 'country_id'])
                ->groupBy('country_id'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'lp' => 'nullable|numeric|min:0.000001',
            'aed' => 'required|numeric|min:0.000001',
            'syp' => 'required|numeric|min:0.000001',
            'commerce' => 'required|array',
            'commerce.*.shipping_fee_usd' => 'required|numeric|min:0',
            'commerce.*.free_shipping_threshold_usd' => 'nullable|numeric|min:0',
            'commerce.*.cod_fee_percent' => 'required|numeric|min:0|max:100',
            'commerce.*.card_enabled' => 'nullable|boolean',
            'commerce.*.gateway_currency' => 'nullable|in:USD',
            'commerce.*.gateway_mode' => 'nullable|in:sandbox,live',
            'commerce.*.website_cashbox_user_id' => 'nullable|integer|exists:users,id',
            'commerce.*.website_stock_user_id' => 'nullable|integer|exists:users,id',
        ]);

        $data = $request->except('_method');
        foreach ($data['commerce'] as $countryId => $commerce) {
            $cashboxUserId = $commerce['website_cashbox_user_id'] ?? null;
            $stockUserId = $commerce['website_stock_user_id'] ?? null;
            if ($cashboxUserId && !User::query()
                ->whereKey($cashboxUserId)
                ->where('country_id', (int) $countryId)
                ->whereIn('role_id', [User::ROLE_SHOP, User::ROLE_WAREHOUSE])
                ->exists()) {
                return back()->withErrors([
                    "commerce.{$countryId}.website_cashbox_user_id" => 'The website cashbox must belong to the same country.',
                ]);
            }
            if ((bool) ($commerce['card_enabled'] ?? false) && !$cashboxUserId) {
                return back()->withErrors([
                    "commerce.{$countryId}.website_cashbox_user_id" => 'Select a website cashbox before enabling card payments.',
                ]);
            }
            if ($stockUserId && !User::query()
                ->whereKey($stockUserId)
                ->where('country_id', (int) $countryId)
                ->whereIn('role_id', [User::ROLE_SHOP, User::ROLE_WAREHOUSE])
                ->exists()) {
                return back()->withErrors([
                    "commerce.{$countryId}.website_stock_user_id" => 'The website stock location must belong to the same country.',
                ]);
            }
        }
        if ($data['lp'] !== null) {
            Currency::query()->updateOrCreate(['name' => 'lp'], ['label' => 'LP', 'rate' => $data['lp']]);
        }
        Currency::query()->updateOrCreate(['name' => 'aed'], ['label' => 'AED', 'rate' => $data['aed']]);
        Currency::query()->updateOrCreate(['name' => 'syp'], ['label' => 'SYP', 'rate' => $data['syp']]);
        app(CurrencyService::class)->clearRateCache();
        foreach (Country::allowedIds() as $countryId) {
            $commerce = $data['commerce'][$countryId] ?? $data['commerce'][(string) $countryId] ?? null;
            if ($commerce) {
                CountryCommerceSetting::query()->updateOrCreate(['country_id' => $countryId], [
                    'shipping_fee_usd' => $commerce['shipping_fee_usd'],
                    'free_shipping_threshold_usd' => $commerce['free_shipping_threshold_usd'],
                    'cod_fee_percent' => $commerce['cod_fee_percent'],
                    'card_enabled' => (bool) ($commerce['card_enabled'] ?? false),
                    'gateway_currency' => strtoupper((string) ($commerce['gateway_currency'] ?? 'USD')),
                    'gateway_mode' => (string) ($commerce['gateway_mode'] ?? 'sandbox'),
                    'website_cashbox_user_id' => $commerce['website_cashbox_user_id'] ?? null,
                    'website_stock_user_id' => $commerce['website_stock_user_id'] ?? null,
                ]);
            }
        }
        $request->session()->flash('success', 'تم تعديل سعر الصرف بنجاح');
        return Redirect::route('currencies.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
