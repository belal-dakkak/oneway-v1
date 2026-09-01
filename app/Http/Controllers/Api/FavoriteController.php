<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\Favorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class FavoriteController extends ApiController
{

    public function addToFavorite(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|numeric|exists:products,id',
        ]);

        $user = $this->getUser($request);
        if (!$user)
            return $this->respondError(__('api.user_not_found'));

        if (Favorite::query()->where('product_id', $request->get('product_id'))->where('user_id', $user->id)->exists()) {
            $favorite = Favorite::query()->where('product_id', $request->get('product_id'))->where('user_id', $user->id)->first();
            $favorite->delete();
            $msg = __('api.item_removed_from_wish_list');
        }else{
            $user->wishList()->attach($request->get('product_id'));
            $msg = __('api.item_added_to_wish_list');
        }

        return $this->respondSuccess($msg);
    }

    public function getFavorite(Request $request): JsonResponse
    {
        $limit = $request->get('limit') ? : 10 ;
        if($limit > 30 ) $limit =30 ;

        $user = $this->getUser($request);
        if (!$user)
            return $this->respondError(__('api.user_not_found'));

        $favorites = $user->wishList()->paginate($limit);

        $favoritesPage = $this->likedByUser($favorites, $user)->all();

        return $this->respondSuccess($favoritesPage, $this->createApiPaginator($favorites));
    }

    public function likedByUser($items, $user){
        return $items->map(function($item) use ($user){
            $item->liked_by = $item->isLikedBy($user);
            return $item;
        });
    }

    public function likedReviewByUser($items, $user){
        return $items->map(function($cartItem) use ($user){
            $item = $cartItem->product;
            $item->liked_by = $item->isLikedBy($user);
            $item->review_by = $item->isReviewBy($user);
            return $cartItem;
        });
    }
}
