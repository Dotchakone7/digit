<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /** Only verified buyers (delivered order) may review, once per product. */
    public function create(User $user, Product $product): bool
    {
        return $user->hasPurchased($product)
            && ! $product->reviews()->where('user_id', $user->id)->exists();
    }

    public function delete(User $user, Review $review): bool
    {
        return $review->user_id === $user->id || $user->can('reviews.moderate');
    }

    public function moderate(User $user, Review $review): bool
    {
        return $user->can('reviews.moderate');
    }
}
