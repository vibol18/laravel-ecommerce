<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    use ApiResponses;

    public function index(Product $product)
    {
        $reviews = $product->reviews()
            ->with('user')
            ->where('is_approved', true)
            ->orderByDesc('created_at')
            ->paginate(15);

        return $this->paginatedResponse(ReviewResource::collection($reviews), 'Reviews retrieved successfully');
    }

    public function store(StoreReviewRequest $request, Product $product)
    {
        $hasPurchased = Order::where('user_id', $request->user()->id)
            ->whereIn('status', ['paid', 'shipped', 'completed'])
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->exists();

        if (! $hasPurchased) {
            return $this->errorResponse('You can only review products you have purchased', 403);
        }

        $existingReview = Review::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existingReview) {
            return $this->errorResponse('You have already reviewed this product', 422);
        }

        $review = Review::create([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
            'rating' => $request->rating,
            'title' => $request->title,
            'comment' => $request->comment,
        ]);

        return $this->successResponse(
            new ReviewResource($review->load('user')),
            'Review created successfully',
            201
        );
    }

    public function show(Request $request, Product $product, Review $review)
    {
        if ($review->product_id !== $product->id) {
            return $this->errorResponse('Review not found for this product', 404);
        }

        return $this->successResponse(new ReviewResource($review->load('user')), 'Review retrieved successfully');
    }

    public function update(StoreReviewRequest $request, Product $product, Review $review)
    {
        if ($review->product_id !== $product->id) {
            return $this->errorResponse('Review not found for this product', 404);
        }

        if ($review->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $review->update($request->validated());

        return $this->successResponse(new ReviewResource($review->load('user')), 'Review updated successfully');
    }

    public function destroy(Request $request, Product $product, Review $review)
    {
        if ($review->product_id !== $product->id) {
            return $this->errorResponse('Review not found for this product', 404);
        }

        if ($review->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $review->delete();

        return $this->successResponse(null, 'Review deleted successfully');
    }
}
