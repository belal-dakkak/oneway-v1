<?php

namespace App\Repositories;

use App\Models\Accessory;
use App\Models\AccessoryLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccessoryRepository
{
    public function add(Request $request): Accessory
    {
        $accessory = new Accessory($request->all(['name', 'count', 'color', 'image']));

        if ($request->get('image') && is_array($request->get('image')))
            $accessory->image = $request->get('image')[0];

        $accessory->user_id = $request->get('user')['id'];
        $accessory->save();
        return $accessory;
    }

    public function update(Accessory $accessory, Request $request)
    {
        if ($exports = $request->get('exports')){
            $accessory->update(['count' => DB::raw("count - $exports")]);
            AccessoryLog::query()->create([
                'count' => $exports,
                'user_id' =>  auth()->id(),
                'accessory_id' => $accessory->id
            ]);
            return $accessory;
        }
        $accessory->update($request->all(['name', 'count', 'color']));

        if ($request->get('image') && is_array($request->get('image')))
            $accessory->image = $request->get('image')[0];

        if ($user = $request->get('user'))
            $accessory->user_id = $user['id'];

        $accessory->save();
        return $accessory;
    }

    public function getAccessories(Request $request): LengthAwarePaginator
    {
        $accessories = Accessory::query()->with('warehouse');

        if ($search = $request->get('search'))
            $accessories->where('name', 'LIKE', "%$search%");

        if ($request->has(['field', 'direction'])){
            $field = $request->get('field');
            $direction = $request->get('direction');

            $sortableArray = app(Accessory::class)->getFillable();
            $sortableArray[] = 'id';
            if(in_array($field, $sortableArray)){
                $accessories->orderBy($field, $direction);
            }else{
                $accessories->orderByDesc('id');
            }
        }else{
            $accessories->orderByDesc('id');
        }

        return $accessories->paginate(10);
    }

}
