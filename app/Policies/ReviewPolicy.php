<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function create(User $user, $productId): bool
    {
        $hasPurchased = Order::where('user_id', $user->id)
            ->whereIn('status', ['paid', 'shipped', 'completed'])
            ->whereHas('items', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->exists();

        return $hasPurchased;
    }

    public function update(User $user, Review $review): bool
    {
        return $review->user_id === $user->id || $user->isAdmin();
    }

    public function delete(User $user, Review $review): bool
    {
        return $review->user_id === $user->id || $user->isAdmin();
    }
}
