<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        abort_unless($product->isPublished(), 404);

        $product->load([
            'category.parent',
            'images',
            'activeVariants',
            'approvedReviews' => fn ($q) => $q->with('user:id,name')->limit(10),
        ]);

        $ratingBreakdown = $product->approvedReviews()->reorder()
            ->selectRaw('rating, COUNT(*) as total')->groupBy('rating')->pluck('total', 'rating');

        // Same category first, then the parent category's family, then best sellers.
        $familyIds = $product->category?->parent?->descendantIds() ?? ($product->category?->descendantIds() ?? []);
        $similar = Product::query()->published()->forCard()
            ->whereKeyNot($product->id)
            ->orderByRaw('CASE WHEN category_id = ? THEN 0 WHEN category_id IN ('.(implode(',', array_map('intval', $familyIds)) ?: '0').') THEN 1 ELSE 2 END', [(int) $product->category_id])
            ->orderByDesc('sales_count')->limit(4)->get();

        $user = auth()->user();

        return view('shop.product', [
            'product' => $product,
            'similar' => $similar,
            'ratingBreakdown' => $ratingBreakdown,
            'canReview' => $user && Gate::forUser($user)->allows('create', [Review::class, $product]),
            'hasReviewed' => $user && $product->reviews()->where('user_id', $user->id)->exists(),
        ]);
    }
}
