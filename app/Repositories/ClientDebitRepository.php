<?php

namespace App\Repositories;

use App\Models\ClientDebitLog;
use App\Models\ClientDebit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ClientDebitRepository
{
    public function getClients(Request $request, $search = null, $amount = null, $amountOperator = '=', $searchPhone = null)
    {
        $clients = ClientDebit::query()->with(['creditor.wallet', 'debtor.wallet', 'payments']);
        $country = auth()->user()->country_id;
        $clients->whereHas('creditor', function ($query) use ($country){
            $query->where('country_id',$country);
        });

        $clients->whereHas('debtor', function ($query) use ($country){
            $query->where('country_id',$country);
        });
        if (auth()->user()->role_id != User::ROLE_ADMIN)
            $clients = $clients->where('creditor_id', auth()->id());

        if ($debtor = $request->get('debtor_id'))
            $clients->where('debtor_id', $debtor);

        if ($debtor = $request->get('shop'))
            $clients->where('creditor_id', $debtor);

        if ($creditor = $request->get('creditor_id'))
            $clients->where('creditor_id', $creditor);

        if (is_string($search))
            $clients->whereRelation('debtor', 'name', 'LIKE', "%$search%");

        if(!is_null($amount))
            $clients->where('amount', $amountOperator, $amount);

        if (is_string($searchPhone))
            $clients->whereRelation('debtor', 'phone', 'LIKE', "%$searchPhone%");

        return $clients->orderByDesc('client_debits.updated_at')->paginate(15);

    }

    public function getClientsSum(Request $request, $search = null, $amount = null, $amountOperator = '=', $searchPhone = null)
    {
        $clients = ClientDebit::query()->with(['creditor.wallet', 'debtor.wallet', 'payments']);
        $country = auth()->user()->country_id;
        $clients->whereHas('creditor', function ($query) use ($country){
            $query->where('country_id',$country);
        });

        $clients->whereHas('debtor', function ($query) use ($country){
            $query->where('country_id',$country);
        });
        if (auth()->user()->role_id != User::ROLE_ADMIN)
            $clients = $clients->where('creditor_id', auth()->id());

        if ($debtor = $request->get('debtor_id'))
            $clients->where('debtor_id', $debtor);

        if ($debtor = $request->get('shop'))
            $clients->where('creditor_id', $debtor);

        if ($creditor = $request->get('creditor_id'))
            $clients->where('creditor_id', $creditor);

        if (is_string($search))
        {
            $clients->join('users', 'client_debits.debtor_id', 'users.id')
            ->where('users.name', 'LIKE', "%$search%");
        }

        if(!is_null($amount))
            $clients->where('amount', $amountOperator, $amount);

        if (is_string($searchPhone))
            $clients->whereRelation('debtor', 'phone', 'LIKE', "%$searchPhone%");

        return $clients->sum('amount');

    }

    public function getDebitLogs($id, Request $request, $pagination = true)
    {

        $log = ClientDebitLog::query()
            ->where('client_debit_id', $id);

        if ($startAt = $request->get('start_date'))
            $log->whereDate('created_at', '>=', Carbon::parse($startAt));

        if ($endAt = $request->get('end_date'))
            $log->whereDate('created_at', '<=', Carbon::parse($endAt));


        if ($pagination)
            $log = $log->orderByDesc('created_at')->paginate(10);
        else
            $log = $log->orderBy('created_at');

        return $log;

    }

}
