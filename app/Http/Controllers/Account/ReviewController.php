<?php

namespace App\Http\Controllers\Account;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $reviewedIds = $user->reviews()->pluck('product_id');

        return view('account.reviews', [
            'reviews' => $user->reviews()->with('product.primaryImage')->latest()->get(),
            // Delivered products the customer has not reviewed yet.
            'toReview' => Product::query()->published()->with('primaryImage')
                ->whereNotIn('id', $reviewedIds)
                ->whereHas('orderItems.order', fn ($q) => $q->where('user_id', $user->id)->where('status', OrderStatus::Delivered))
                ->limit(6)->get(),
        ]);
    }

    public function destroy(Review $review): RedirectResponse
    {
        Gate::authorize('delete', $review);

        $product = $review->product;
        $review->delete();
        $product?->refreshRating();

        return back()->with('toast', ['type' => 'success', 'message' => 'Avis supprimé.']);
    }
}
