<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\City;
use App\Models\ShippingDetails;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AddressController extends ApiController
{

    public function cities()
    {
        $cities = City::query()->get();

        return $this->respondSuccess($cities);
    }

    public function index(Request $request): JsonResponse
    {
        $limit = $request->get('limit') ?: 100;
        if ($limit > 100) $limit = 100;

        $user = $this->getUser($request);
        if (!$user)
            return $this->respondError(__('api.user_not_found'));

        $shippingAddresses = $user->shippingDetails()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($limit);

        return $this->respondSuccess($shippingAddresses->all(), $this->createApiPaginator($shippingAddresses));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'label' => 'required|string',
            'address' => 'required|string',
            'city_id' => 'required|exists:cities,id',
        ]);

        $user = $this->getUser($request);
        if (!$user)
            return $this->respondError(__('api.user_not_found'));

        $address = ShippingDetails::create([
            'label' => $request->get('label'),
            'address' => $request->get('address'),
            'city_id' => $request->get('city_id') ?? $request->get('city'),
            'phone' => $request->get('phone'),
            'building' => $request->get('building'),
            'apartment' => $request->get('apartment'),
            'user_id' => $user->id,
        ]);

        if ($address instanceof ShippingDetails) {
            return $this->respondSuccess($address, __('api.added_successfully'));
        } else {
            $address->delete();
            return $this->respondError(__('api.item_not_found'));
        }

    }

    public function update(Request $request,  $id): JsonResponse
    {
        $user = $this->getUser($request);
        if (!$user)
            return $this->respondError(__('api.user_not_found'));

        $address = ShippingDetails::query()->where(['user_id'=>$user->id ,'id'=>$id])->first();
        if (!$address)
            return $this->respondError(__('api.item_not_found'));

        if ($request->has('address'))
            $address->update(['address'=>$request->get('address')]);

        if ($request->has('city_id'))
            $address->update(['city_id'=>$request->get('city_id')]);

        if ($request->has('city'))
            $address->update(['city_id'=>$request->get('city')]);

        if ($request->has('label'))
            $address->update(['label'=>$request->get('label')]);

        if ($request->has('phone'))
            $address->update(['phone'=>$request->get('phone')]);

        if ($request->has('apartment'))
            $address->update(['apartment'=>$request->get('apartment')]);

        if ($request->has('building'))
            $address->update(['building'=>$request->get('building')]);

        return $this->respondSuccess($address, __('api.added_successfully'));

    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $this->getUser($request);
        if (!$user)
            return $this->respondError(__('api.user_not_found'));

        $address = ShippingDetails::query()->where(['user_id'=>$user->id ,'id'=>$request->get('address')])->first();


        if (!$address)
            return $this->respondError(__('api.item_not_found'));


        $address->delete();

        return $this->respondSuccess( __('api.items_deletes_successfully'));
    }

}
