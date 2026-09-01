<?php

namespace App\Repositories;

use App\Models\Fabric;
use App\Models\FabricLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FabricRepository
{
    public function add(Request $request): Fabric
    {
        $fabric = new Fabric($request->all(['name', 'yards', 'color', 'image']));

        if ($request->get('image') && is_array($request->get('image')))
            $fabric->image = $request->get('image')[0];

        $fabric->count = 1;
        $fabric->user_id = $request->get('user')['id'];
        $fabric->save();
        return $fabric;
    }

    public function update(Fabric $fabric, Request $request)
    {
        if ($exports = $request->get('exports')){
            $fabric->update(['yards' => DB::raw("yards - $exports")]);
            FabricLog::query()->create([
                'count' => $exports,
                'user_id' =>  auth()->id(),
                'fabric_id' => $fabric->id
            ]);
            return $fabric;
        }
        $fabric->update($request->all(['name', 'yards', 'color']));

        if ($request->get('image') && is_array($request->get('image')))
            $fabric->image = $request->get('image')[0];

        if ($user = $request->get('user'))
            $fabric->user_id = $user['id'];

        $fabric->save();
        return $fabric;
    }

    public function getFabrics(Request $request): LengthAwarePaginator
    {
        $fabrics = Fabric::query()->with('warehouse');
        $country = auth()->user()->country_id;
        $fabrics->whereHas('warehouse', function ($query) use ($country){
            $query->where('country_id',$country);
        });
        if ($search = $request->get('search'))
            $fabrics->where('name', 'LIKE', "%$search%");

        if ($request->has(['field', 'direction'])){
            $field = $request->get('field');
            $direction = $request->get('direction');

            $sortableArray = app(Fabric::class)->getFillable();
            $sortableArray[] = 'id';
            if(in_array($field, $sortableArray)){
                $fabrics->orderBy($field, $direction);
            }else{
                $fabrics->orderByDesc('id');
            }
        }else{
            $fabrics->orderByDesc('id');
        }

        return $fabrics->paginate(10);
    }

}
