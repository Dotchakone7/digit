<?php

namespace App\Http\Controllers\Shop;

use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\StoreReviewRequest;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request, Product $product): RedirectResponse
    {
        $order = $request->user()->orders()
            ->where('status', OrderStatus::Delivered)
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->latest()->first();

        $review = new Review($request->validated() + ['product_id' => $product->id, 'user_id' => $request->user()->id, 'order_id' => $order?->id]);
        $review->status = ReviewStatus::Pending; // Published only after moderation.
        $review->save();

        return redirect()->to(route('products.show', $product).'#avis')
            ->with('toast', ['type' => 'success', 'message' => 'Merci ! Votre avis sera publié après validation.']);
    }
}
