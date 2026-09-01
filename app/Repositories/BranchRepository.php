<?php

namespace App\Repositories;

use App\Models\Branch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class BranchRepository
{
    public function add(Request $request): Branch
    {
        $branch =  new Branch($request->all());
        $branch->save();
        return $branch;
    }

    public function update(Request $request, Branch $branch)
    {
        $branch->update($request->all());
    }

    public function getBranches(Request $request): LengthAwarePaginator
    {
        $branches = Branch::query();

        if ($search = $request->get('search'))
            $branches->where('name_en', 'LIKE', "%$search%")
                ->orWhere('name_ar', 'LIKE', "%$search%");

        if ($request->has(['field', 'direction'])){
            $field = $request->get('field');
            $direction = $request->get('direction');

            $sortableArray = app(Branch::class)->getFillable();
            $sortableArray[] = 'id';
            if(in_array($field, $sortableArray)){
                $branches->orderBy($field, $direction);
            }else{
                $branches->orderByDesc('id');
            }
        }else{
            $branches->orderByDesc('id');
        }

        return $branches->paginate(10);
    }

}
