<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;

class WishlistService
{
    private ?array $ids = null;

    private ?int $userId = null;

    /** Product IDs in the current user's wishlist (one query per request). */
    public function ids(): array
    {
        $user = auth()->user();

        if ($this->ids !== null && $this->userId === $user?->id) {
            return $this->ids;
        }

        $this->userId = $user?->id;

        return $this->ids = $user ? $user->wishlist()->pluck('products.id')->all() : [];
    }

    public function has(Product $product): bool
    {
        return in_array($product->id, $this->ids(), true);
    }

    /** @return bool true when the product is now in the wishlist */
    public function toggle(User $user, Product $product): bool
    {
        $result = $user->wishlist()->toggle($product->id);
        $this->ids = null;

        return $result['attached'] !== [];
    }
}
