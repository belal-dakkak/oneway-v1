<?php

namespace App\Repositories;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class ExpenseRepository
{

    public function add(Request $request): Expense
    {
        $expense = new Expense($request->all());
        $expense->issuer_id = auth()->id();
        if ($user = $request->get('user'))
        $expense->consumer_id = $user['id'];
        $expense->save();

        return $expense;
    }

    public function getExpenses(Request $request): LengthAwarePaginator
    {
        $expenses = Expense::query()->with(['issuer', 'consumer']);
        $country = auth()->user()->country_id;
         $expenses->whereHas('issuer', function ($query) use ($country){
             $query->where('country_id',$country);
         });
        // $expenses->whereHas('consumer', function ($query) use ($country){
        //     $query->where('country_id',$country);
        // });
        if ($search = $request->get('search'))
            $expenses->where('description', 'LIKE', "%$search%");

        if (!in_array(auth()->user()->role_id, [User::ROLE_ADMIN, User::ROLE_WAREHOUSE]))
            $expenses->where('issuer_id', auth()->id());

        if ($issuer = $request->get('issuer_id'))
            $expenses->where('issuer_id', $issuer);

        if ($consumer = $request->get('consumer_id'))
            $expenses->where('consumer_id', $consumer);

        if ($request->has(['field', 'direction'])){
            $field = $request->get('field');
            $direction = $request->get('direction');

            $sortableArray = app(Expense::class)->getFillable();
            $sortableArray[] = 'id';
            if(in_array($field, $sortableArray)){
                $expenses->orderBy($field, $direction);
            }else{
                $expenses->orderByDesc('id');
            }
        }else{
            $expenses->orderByDesc('id');
        }

        return $expenses->paginate(10);
    }

}
