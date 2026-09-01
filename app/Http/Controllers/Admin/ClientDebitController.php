<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientDebit;
use App\Models\ClientDebitLog;
use App\Models\ClientDebitPayment;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Repositories\ClientDebitRepository;
use PDF;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;
use Jenssegers\Date\Date;

class ClientDebitController extends Controller
{

    private $debitRepository;

    public function __construct(ClientDebitRepository $debitRepository)
    {
        $this->debitRepository = $debitRepository;
    }

    public function show(ClientDebit $debit): Response
    {
        $payments = $debit->payments()->get();

        $payments = transformDataForVue($payments);
        $creditor = $debit->creditor;
        $debtor = $debit->debtor;

        return Inertia::render('Admin/ClientDebits/Show', [
            'payments' => $payments,
            'debit' => $debit,
            'debtor' => $debtor,
            'creditor' => $creditor,
        ]);
    }

    public function addPayment(Request $request): JsonResponse
    {
        $trivialDifference = false;

        try {
            DB::beginTransaction();

            $debit     = ClientDebit::query()->findOrFail($request->get('debit'));
            $amount    = $request->get('amount');
            $amountLog = number_format($amount, 2);
            $rate      = 1;

            if(auth()->check() && auth()->user()->country_id == User::COUNTRY_UAE) {
                $rate = Currency::where('name','aed')->first()->rate;
                $amount = $amount / $rate;
            }

            $postAmount        = $debit->amount - $amount; // Amount after transaction
            $trivialDifference = abs($postAmount) < 1;

            if($trivialDifference)
                $amount = $debit->amount;
            else if($amount > $debit->amount) {
                return response()->json([
                    'error' => 'المبلغ اكبر من القيمة المستحقة'
                ]);
            }

            $debit->update(['amount' => DB::raw("amount - $amount")]);

            $debitPayment = ClientDebitPayment::query()->create([
                'amount' => $amount,
                'client_debit_id' => $debit->id
            ]);
            $client = $debit->debtor->name;
            $shop = $debit->creditor->name;
            $note = "قام الزبون $client بتسديد دفعة $amountLog للمحل $shop";

            ClientDebitLog::query()->create([
                'amount' => $amount,
                'client_debit_id' => $debit->id,
                'client_debit_payment_id' => $debitPayment->id,
                'note' => $note,
            ]);

            $debit->creditor->wallet->update(['credit' => DB::raw("credit + $amount")]);
            $debit->debtor->wallet->update(['debit' => DB::raw("debit - $amount")]);
        } catch (Exception $exception) {
            DB::rollBack();
            return response()->json([
                'error' => $exception->getMessage()
            ]);
        }
        DB::commit();

        if($trivialDifference)
            $this->close($request);

        return response()->json([
            'success' => true
        ]);
    }

    public function close(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            $debit = ClientDebit::query()->findOrFail($request->get('debit'));

            $debit->update(['amount' => 0]);
            Order::query()
                ->where('buyer_id', $debit->debtor_id)
                ->where('seller_id', $debit->creditor_id)
                ->update([
                    'paid_price' => DB::raw("total_price"),
                    'remain_price' => 0
                ]);

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

    public function addWithdraw(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $debit = ClientDebit::query()->findOrFail($request->get('debit'));
            $client = $debit->debtor;

            $amount = $request->get('amount');

            $amount    = $request->get('amount');
            $amountLog = number_format($amount, 2);
            $rate      = 1;

            if(auth()->check() && auth()->user()->country_id == User::COUNTRY_UAE) {
                $rate = Currency::where('name','aed')->first()->rate;
                $amount = $amount / $rate;
            }

            $postAmount        = $debit->amount - $amount; // Amount after transaction
            $trivialDifference = abs($postAmount) < 1;

            if($trivialDifference)
                $amount = $client->wallet->credit;
            else if ($amount > $client->wallet->credit){
                return response()->json([
                    'error' => 'المبلغ اكبر من القيمة المستحقة'
                ]);
            }

            $shop   = $debit->creditor->name;
            $client = $debit->debtor->name;

            $note = "قام الزبون $client بسحب دفعة $amountLog من المحل $shop";

            ClientDebitLog::query()->create([
                'amount' => $amount,
                'client_debit_id' => $debit->id,
                'note' => $note
            ]);

            $debit->debtor->wallet->update(['credit' => DB::raw("credit - $amount")]);
            $debit->creditor->wallet->update(['credit' => DB::raw("credit - $amount")]);

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
     * Display a listing of the clients.
     */
    public function clients(Request $request)
    {
        $request->validate([
            'direction' => ['in:asc,desc', 'nullable'],
            'field' => ['in:id', 'nullable']
        ]);

        $search      = $request->query('search', null);
        $searchPhone = $request->query('searchPhone', null);

        $clients = $this->debitRepository->getClients($request, $search, null, null, $searchPhone);

        $sum     = $this->debitRepository->getClientsSum($request, $search, null, null, $searchPhone);

        if ($request->wantsJson()){
            return ['debits' => $clients, 'sum' => $sum];
        }

        $shops = User::query()->whereIn('role_id', [User::ROLE_SHOP, User::ROLE_WAREHOUSE])->where('country_id',auth()->user()->country_id)->get();
        $shops = transformDataForVue($shops);
        if(auth()->user()->country_id == User::COUNTRY_UAE)
            $rate = Currency::where('name','aed')->first()->rate;
        else $rate = 1;

        return Inertia::render('Admin/ClientDebits/Clients', [
            'clients' => $clients,
            'shops' => $shops,
            'sum' => $sum,
            'rate' => $rate,
            'filters' => $request->all(['search', 'field', 'direction'])
        ]);
    }

    public function payments($id)
    {
        $debit = ClientDebit::query()->findOrFail($id);
        $payments = $debit->payments()->get();

        $payments = transformDataForVue($payments);
        $creditor = $debit->creditor;
        $debtor = $debit->debtor;

        return Inertia::render('Admin/ClientDebits/Client', [
            'payments' => $payments,
            'debit' => $debit,
            'debtor' => $debtor,
            'creditor' => $creditor,
        ]);
    }

    public function log($id, Request $request)
    {
        $debit = ClientDebit::query()->findOrFail($id);
        $log = $this->debitRepository->getDebitLogs($id, $request);

        $creditor = $debit->creditor;
        $debtor = $debit->debtor;
        if(auth()->check() && auth()->user()->country_id == 2){
            $rate = Currency::where('name','aed')->first()->rate;
        }else{
            $rate = 1;
        }

        //dd($log);

        return Inertia::render('Admin/ClientDebits/Log', [
            'rate' => $rate,
            'logs' => $log,
            'debit' => $debit,
            'debtor' => $debtor,
            'creditor' => $creditor,
            'filters' => $request->all(['search', 'field', 'direction', 'start_date', 'end_date'])
        ]);
    }

    public function clientAccountLog($id, Request $request)
    {

        $debit = ClientDebit::query()->findOrFail($id);
        $log = $this->debitRepository->getDebitLogs($id, $request, false);

        $creditor = $debit->creditor;
        $debtor = $debit->debtor;

        $totalPaid = $debit->log->whereNull('order_id')->whereNull('client_refund_id')->sum('amount');
        $totalAccount = $debit->log->whereNotNull('order_id')->sum('amount');

        $totalRefund = $debit->refunds->sum(function ($refund){
            return $refund->refund->total_price;
        });

        $log = $log->get();
        if($debit->creditor->country_id == 2){
            $rate = Currency::where('name','aed')->first()->rate;
			$Currency = 'AED';
        }else{
            $rate = 1;
			$Currency = 'USD';
        }

        Date::setLocale('ar');
        $now =  Date::parse(now())->timezone('Asia/Beirut')->format('d-m-Y h:i a');
        $language  = 'en';
        $country   = Session::get('country') == 'LB'?User::COUNTRY_LB:User::COUNTRY_UAE;
        $settings = Setting::where('country',$country)->where('language',$language)->pluck('value','name')->toArray();
        $pdf = PDF::loadView('includes.log_template',array('log'=>$log,'settings'=>$settings, 'creditor' => $creditor, 'debtor' => $debtor, 'debit' => $debit, 'now' => $now, 'totalPaid' => $totalPaid, 'totalAccount' => $totalAccount, 'totalRefund' => $totalRefund,'rate' => $rate,'Currency' => $Currency));
        return $pdf->download('Invoice_'.config('app.name').'_Acc_No # '.$id.'.pdf');
        return view('receipts.pdfMerchantAccount', compact('log', 'creditor', 'debtor', 'debit', 'now', 'totalPaid', 'totalAccount', 'totalRefund','rate','Currency'));
    }


}
