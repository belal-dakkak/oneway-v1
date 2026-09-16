<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientDebit;
use App\Models\ClientDebitLog;
use App\Models\ClientDebitPayment;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Repositories\ClientDebitRepository;
use App\Services\CurrencyService;
use App\Services\ClientAccountService;
use App\Support\Country;
use PDF;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Jenssegers\Date\Date;

class ClientDebitController extends Controller
{

    private $debitRepository;
    private $clientAccounts;

    public function __construct(ClientDebitRepository $debitRepository, ClientAccountService $clientAccounts)
    {
        $this->debitRepository = $debitRepository;
        $this->clientAccounts = $clientAccounts;
    }

    public function show(ClientDebit $debit): Response
    {
        $debit = $this->authorizedAccount((int) $debit->id);
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
        try {
            $request->validate(['debit' => 'required|integer', 'amount' => 'required|numeric|min:0.0001']);
            $debit = $this->authorizedAccount((int) $request->get('debit'));
            $this->clientAccounts->payAccount($debit, (float) $request->get('amount'));
        } catch (Exception $exception) {
            return response()->json([
                'error' => $exception->getMessage()
            ], 422);
        }

        return response()->json([
            'success' => true
        ]);
    }

    public function close(Request $request): JsonResponse
    {
        try {
            $debit = $this->authorizedAccount((int) $request->get('debit'));
            if ((float) $debit->amount > 0) {
                $this->clientAccounts->payAccount($debit, (float) $debit->amount);
            }
        }catch (Exception $exception){
            return response()->json([
                'error' => $exception->getMessage()
            ], 422);
        }

        return response()->json([
            'success' => true
        ]);
    }

    public function addWithdraw(Request $request): JsonResponse
    {
        try {
            $request->validate(['debit' => 'required|integer', 'amount' => 'required|numeric|min:0.0001']);
            $debit = $this->authorizedAccount((int) $request->get('debit'));
            $this->clientAccounts->withdrawCredit($debit, (float) $request->get('amount'));
        }catch (Exception $exception){
            return response()->json([
                'error' => $exception->getMessage()
            ], 422);
        }

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
        return Inertia::render('Admin/ClientDebits/Clients', [
            'clients' => $clients,
            'shops' => $shops,
            'sum' => $sum,
            'rate' => 1,
            'filters' => $request->all(['search', 'field', 'direction'])
        ]);
    }

    public function payments($id)
    {
        $debit = $this->authorizedAccount((int) $id);
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
        $debit = $this->authorizedAccount((int) $id);
        $log = $this->debitRepository->getDebitLogs($id, $request);

        $creditor = $debit->creditor;
        $debtor = $debit->debtor;
        $rate = 1;

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

        $debit = $this->authorizedAccount((int) $id);
        $log = $this->debitRepository->getDebitLogs($id, $request, false);

        $creditor = $debit->creditor;
        $debtor = $debit->debtor;

        $totalPaid = $debit->payments->sum('amount');
        $totalAccount = $debit->log->whereNotNull('order_id')->sum('amount');
        $totalRefund = abs($debit->log->whereNotNull('client_refund_id')->sum('amount'));

        $log = $log->get();
        $country = $debit->creditor->country_id;
        $Currency = strtoupper((string) ($debit->currency_code ?: 'USD'));
        $rate = 1;

        Date::setLocale('ar');
        $now = Date::parse(now())->timezone(Country::timezone($country))->format('d-m-Y h:i a');
        $language  = 'en';
        $settings = Setting::where('country',$country)->where('language',$language)->pluck('value',Setting::keyColumn())->toArray();
        $pdf = PDF::loadView('includes.log_template',array('log'=>$log,'settings'=>$settings, 'creditor' => $creditor, 'debtor' => $debtor, 'debit' => $debit, 'now' => $now, 'totalPaid' => $totalPaid, 'totalAccount' => $totalAccount, 'totalRefund' => $totalRefund,'rate' => $rate,'Currency' => $Currency));
        return $pdf->download('Invoice_'.config('app.name').'_Acc_No # '.$id.'.pdf');
        return view('receipts.pdfMerchantAccount', compact('log', 'creditor', 'debtor', 'debit', 'now', 'totalPaid', 'totalAccount', 'totalRefund','rate','Currency'));
    }

    private function authorizedAccount(int $id): ClientDebit
    {
        return ClientDebit::query()
            ->whereHas('creditor', function ($query) {
                $query->where('country_id', auth()->user()->country_id);
            })
            ->when(auth()->user()->role_id !== User::ROLE_ADMIN, function ($query) {
                $query->where('creditor_id', auth()->id());
            })
            ->findOrFail($id);
    }


}
