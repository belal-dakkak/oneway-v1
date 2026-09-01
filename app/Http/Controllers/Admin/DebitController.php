<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Debit;
use App\Models\DebitLog;
use App\Models\DebitPayment;
use App\Models\MerchantDebit;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\DebitRepository;
use App\Services\CurrencyService;
use App\Support\Country;
use Carbon\Carbon;
use PDF;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Jenssegers\Date\Date;

class DebitController extends Controller

{

    private $debitRepository;

    public function __construct(DebitRepository $debitRepository)
    {
        $this->debitRepository = $debitRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $request->validate([
            'direction' => ['in:asc,desc', 'nullable'],
            'field' => ['in:creditor_id,id,debtor_id', 'nullable']
        ]);

        $debits = $this->debitRepository->getDebits($request);
        $shops = User::query()->whereIn('role_id', [User::ROLE_SHOP, User::ROLE_WAREHOUSE])->get();
        $shops = transformDataForVue($shops);

        return Inertia::render('Admin/Debits/Index', [
            'debits' => $debits,
            'shops' => $shops,
            'filters' => $request->all(['search', 'field', 'direction'])
        ]);
    }

    public function pay($id)
    {
        $debit = Debit::query()->findOrFail($id);
        $debit->update(['paid_at' => Carbon::now()]);
        $amount = $debit->amount;
        $debit->creditor->wallet->update(['credit' => DB::raw("credit - $amount")]);
        $debit->debtor->wallet->update(['debit' => DB::raw("debit - $amount")]);

        return Redirect::route('debits.index');
    }

    public function show(Debit $debit)
    {
        $payments = $debit->payments()->get();

        $payments = transformDataForVue($payments);
        $creditor = $debit->creditor;
        $debtor = $debit->debtor;

        return Inertia::render('Admin/Debits/Show', [
            'payments' => $payments,
            'debit' => $debit,
            'debtor' => $debtor,
            'creditor' => $creditor,
        ]);
    }

    public function addPayment(Request $request)
    {
        try {
            DB::beginTransaction();

            $debit = MerchantDebit::query()->findOrFail($request->get('debit'));
            $amount = $request->get('amount');
            $originalAmount = number_format((float)$amount, 2, '.', '');
            $rate = app(CurrencyService::class)->rate(Country::defaultCurrency(auth()->user()->country_id));
            if ($rate != 1.0) {
                $amount = $amount / $rate;
            }

            if ($amount > $debit->amount){
                return response()->json([
                    'error' => 'المبلغ اكبر من القيمة المستحقة'
                ]);
            }

            $debit->update(['amount' => DB::raw("amount - $amount")]);

            $debitPayment = DebitPayment::query()->create([
                'amount' => $amount,
                'merchant_debit_id' => $debit->id
            ]);
            $shop = $debit->debtor->name;
            $merchant = $debit->creditor->name;
            $note = "قام المحل $shop بتسديد دفعة $originalAmount للتاجر $merchant";

            DebitLog::query()->create([
                'amount' => $amount,
                'merchant_debit_id' => $debit->id,
                'debit_payment_id' => $debitPayment->id,
                'note' => $note
            ]);

            if (auth()->user()->role_id != User::ROLE_ADMIN){
                $debit->creditor->wallet->update(['credit' => DB::raw("credit - $amount")]);

                $debit->debtor->wallet->update([
                    'debit' => DB::raw("debit - $amount"),
                    'credit' => DB::raw("credit - $amount")
                ]);
            }


        }catch (Exception $exception){
            DB::rollBack();
            return response()->json([
                'error' => $exception->getMessage()
            ]);
        }
        DB::commit();

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Display a listing of the merchants.
     */
    public function merchants(Request $request)
    {
        $request->validate([
            'direction' => ['in:asc,desc', 'nullable'],
            'field' => ['in:id', 'nullable']
        ]);

        $merchants = $this->debitRepository->getMerchants($request);
        $belalMerchants = $this->debitRepository->getBelalMerchants($request);

        if ($request->wantsJson()){
            return $merchants;
        }

        $shops = User::query()->whereIn('role_id', [User::ROLE_SHOP, User::ROLE_WAREHOUSE])->where('country_id',auth()->user()->country_id)->get();
        $shops = transformDataForVue($shops);

        $belalDebit = $this->debitRepository->getBelalDebit();
        $belalMerchant = transformDataForVue($belalMerchants);
        $rate = app(CurrencyService::class)->rate(Country::defaultCurrency(auth()->user()->country_id));
        return Inertia::render('Admin/Debits/Merchants', [
            'merchants' => $merchants,
            'belal_debit' => $belalDebit,
            'belal_merchant' => $belalMerchant,
            'shops' => $shops,
            'rate' => $rate,
            'filters' => $request->all(['search', 'field', 'direction'])
        ]);
    }

    public function payments($id)
    {
        $debit = MerchantDebit::query()->findOrFail($id);
        $payments = $debit->payments()->get();

        $payments = transformDataForVue($payments);
        $creditor = $debit->creditor;
        $debtor = $debit->debtor;

        return Inertia::render('Admin/Debits/Merchant', [
            'payments' => $payments,
            'debit' => $debit,
            'debtor' => $debtor,
            'creditor' => $creditor,
        ]);
    }

    public function log($id, Request $request)
    {
        $debit = MerchantDebit::query()->findOrFail($id);
        $log = $this->debitRepository->getDebitLogs($id, $request);

        $creditor = $debit->creditor;
        $debtor = $debit->debtor;
        $rate = app(CurrencyService::class)->rate(Country::defaultCurrency(auth()->user()->country_id));
        return Inertia::render('Admin/Debits/Log', [
            'logs' => $log,
            'debit' => $debit,
            'rate' => $rate,
            'debtor' => $debtor,
            'creditor' => $creditor,
            'filters' => $request->all(['search', 'field', 'direction', 'start_date', 'end_date'])
        ]);
    }

    public function merchantAccountLog($id, Request $request)
    {
        $debit = MerchantDebit::query()->findOrFail($id);
        $log = $this->debitRepository->getDebitLogs($id, $request, false);

        $creditor = $debit->creditor;
        $debtor = $debit->debtor;

        $totalPaid = $debit->log()->whereNull('user_product_id')->whereNull('merchant_refund_id')->sum('amount');
        $totalAccount = $debit->log()->whereNotNull('user_product_id')->sum('amount');
        $totalRefund = $debit->refunds->sum(function ($refund){
            return $refund->userProduct->wholesale_price??0 * $refund->qty;
        });

        $log = $log->get();
        Date::setLocale('ar');
        $now = Date::parse(now())->timezone(Country::timezone(auth()->user()->country_id))->format('d-m-Y h:i a');
        $language  = 'en';
        $country = auth()->user()->country_id;
        $Currency = Country::defaultCurrency($country);
        $rate = app(CurrencyService::class)->rate($Currency);
				
        $settings = Setting::where('country',$country)->where('language',$language)->pluck('value','name')->toArray();
        $pdf = PDF::loadView('includes.log_template',array('log'=>$log,'settings'=>$settings, 'creditor' => $creditor, 'debtor' => $debtor, 'debit' => $debit, 'now' => $now, 'totalPaid' => $totalPaid, 'totalAccount' => $totalAccount, 'totalRefund' => $totalRefund,'rate' => $rate,'Currency' => $Currency));
        return $pdf->download('Invoice_'.config('app.name').'_Acc_No # '.$id.'.pdf');
        return view('receipts.pdfMerchantAccount', compact('log', 'creditor', 'debtor', 'debit', 'now', 'totalPaid', 'totalAccount','totalRefund','Currency'));
    }


}
