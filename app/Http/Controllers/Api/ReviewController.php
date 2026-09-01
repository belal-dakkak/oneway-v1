<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


class ReviewController extends ApiController
{

    public function review(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'review' => ['required', Rule::in([1, 2, 3, 4, 5])]
        ]);

        $user = $this->getUser($request);
        if (!$user)
            return $this->respondError(__('api.user_not_found'));

        $productId = $request->get('product_id');

        if (Review::whereUserId($user->id)->whereProductId($productId)->exists()) {
            $review = Review::whereUserId($user->id)->whereProductId($productId)->first();
            $review->update([
                'review' => $request->get('review'),
                'review_content' => $request->get('review_content') ?? null
            ]);
        } else {
            $review = $this->createReview($request, $user);
            $review->save();
        }

        $review->product->calculateRate();
        return $this->respondSuccess(__('api.added_successfully'));

    }


    public function reviews(Request $request): JsonResponse
    {
        $user = $this->getUser($request);
        if (!$user)
            return $this->respondError(__('api.user_not_found'));

        $favorites = $user->reviews()->get();
        return $this->respondSuccess($favorites);
    }


    public function productReviews($id, Request $request): JsonResponse
    {
        $product = Product::query()
            ->where('id', $id)
            ->first();

        if (!$product)
            return $this->respondError(__('api.product_not_found'));

        $ratings = [
            '1' => 0,
            '2' => 0,
            '3' => 0,
            '4' => 0,
            '5' => 0,
        ];
        $comments = array();

        foreach ($product->reviews as $review) {
            isset($ratings[$review->review]) ? $ratings[$review->review] += 1 : $ratings[$review->review] = 1;
            $comments[] = [
                'user' => $review->user,
                'create_date' => $review->created_at->diffForHumans(),
                'review_content' => ['content' => $review->review_content, 'review' => $review->review],
            ];
        }
        $data = [
            'rate' => $product->rate,
            'count' => $product->reviews()->count(),
                'r1' => $ratings[1],
                'r2' => $ratings[2],
                'r3' => $ratings[3],
                'r4' => $ratings[4],
                'r5' => $ratings[5],
            'comments' => $comments ?? [],
        ];
        return $this->respondSuccess($data);
    }

    public function createReview(Request $request, $user): Review
    {
        return new Review([
            'user_id' => $user->id,
            'product_id' => $request->get('product_id'),
            'review' => $request->get('review'),
            'review_content' => $request->get('review_content') ?? null,
        ]);
    }
}
