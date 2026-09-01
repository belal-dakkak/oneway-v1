<?php

namespace App\Repositories;

use App\Models\Debit;
use App\Models\DebitLog;
use App\Models\MerchantDebit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class DebitRepository
{

    public function getDebits(Request $request): LengthAwarePaginator
    {
        $debits = Debit::query()->with(['creditor', 'debtor', 'userProduct', 'userProductLog']);

        if (auth()->user()->role_id != User::ROLE_ADMIN)
            $debits->where('debtor_id', auth()->id());

        if ($debtor = $request->get('debtor_id'))
            $debits->where('debtor_id', $debtor);

        if ($debtor = $request->get('shop'))
            $debits->where('debtor_id', $debtor);

        if ($debtor = $request->get('debtor_id'))
            $debits->where('debtor_id', $debtor);

        if ($creditor = $request->get('creditor_id'))
            $debits->where('creditor_id', $creditor);

        if ($debtor = $request->get('debtor'))
            $debits->where('debtor_id', $debtor);

        if ($creditor = $request->get('creditor'))
            $debits->where('creditor_id', $creditor);

        if ($request->has(['field', 'direction'])){
            $field = $request->get('field');
            $direction = $request->get('direction');

            $sortableArray = app(Debit::class)->getFillable();
            $sortableArray[] = 'id';
            if(in_array($field, $sortableArray)){
                $debits->orderBy($field, $direction);
            }else{
                $debits->orderByDesc('id');
            }
        }else{
            $debits->orderByDesc('id');
        }

        return $debits->paginate(10);
    }

    public function getBelalMerchants(Request $request)
    {
        $merchants = MerchantDebit::query()->with(['creditor', 'debtor', 'payments']);
        $country = auth()->user()->country_id;
        $merchants->whereHas('creditor', function ($query) use ($country){
            $query->where('country_id',$country);
        });

        $merchants->whereHas('debtor', function ($query) use ($country){
            $query->where('country_id',$country);
        });
        if (auth()->user()->role_id != User::ROLE_ADMIN)
            $merchants = $merchants->where('debtor_id', auth()->id());

        return $merchants->where('creditor_id', 95)->get();
    }

    public function getMerchants(Request $request)
    {
        $merchants = MerchantDebit::query()->with(['creditor', 'debtor', 'payments']);
        $country   = auth()->user()->country_id;
        $merchants->whereHas('creditor', function ($query) use ($country){
            $query->where('country_id',$country);
        });

        $merchants->whereHas('debtor', function ($query) use ($country){
            $query->where('country_id',$country);
        });

        if (!in_array(auth()->user()->role_id, [User::ROLE_ADMIN, User::ROLE_WAREHOUSE]))
            $merchants = $merchants->where('debtor_id', auth()->id());

        $merchants = $merchants->where('creditor_id', '!=', 95);

        if ($debtor = $request->get('debtor_id'))
            $merchants->where('debtor_id', $debtor);

        if ($debtor = $request->get('shop'))
            $merchants->where('debtor_id', $debtor);

        if ($creditor = $request->get('creditor_id'))
            $merchants->where('creditor_id', $creditor);

        return $merchants->orderByDesc('id')->paginate(100);

    }

    public function getDebitLogs($id, Request $request, $pagination = true)
    {

        $log = DebitLog::query()
            ->where('merchant_debit_id', $id);

        if ($startAt = $request->get('start_date'))
            $log->whereDate('created_at', '>=', Carbon::parse($startAt));

        if ($endAt = $request->get('end_date'))
            $log->whereDate('created_at', '<=', Carbon::parse($endAt));

        if ($checked = $request->get('checked'))
            $log->whereIn('id', $checked);

        if ($pagination)
            $log = $log->orderByDesc('created_at')->paginate(10);
        else
            $log = $log->orderBy('created_at');

        return $log;

    }

    public function getBelalDebit()
    {
        $belalDebit = MerchantDebit::query();
        $country = auth()->user()->country_id;
        $belalDebit->whereHas('creditor', function ($query) use ($country){
            $query->where('country_id',$country);
        });

        $belalDebit->whereHas('debtor', function ($query) use ($country){
            $query->where('country_id',$country);
        });
        if (auth()->user()->role_id != User::ROLE_ADMIN)
            $belalDebit = $belalDebit->where('debtor_id', auth()->id());

        return ceil($belalDebit->where('creditor_id', 95)->sum('amount'));
    }

}
