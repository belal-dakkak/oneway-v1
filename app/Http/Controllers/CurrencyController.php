<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\CountryCommerceSetting;
use App\Services\CurrencyService;
use App\Support\Country;
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
        ]);

        $data = $request->except('_method');
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
