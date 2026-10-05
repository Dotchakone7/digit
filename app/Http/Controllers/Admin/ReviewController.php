<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->validate(['status' => ['nullable', Rule::enum(ReviewStatus::class)]])['status'] ?? ReviewStatus::Pending->value;

        return view('admin.reviews.index', [
            'reviews' => Review::query()->with(['product:id,name,slug', 'user:id,name,email'])
                ->where('status', $status)->latest()->paginate(20)->withQueryString(),
            'status' => ReviewStatus::from($status),
            'counts' => Review::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function update(Request $request, Review $review): RedirectResponse
    {
        $status = ReviewStatus::from($request->validate(['status' => ['required', Rule::in([ReviewStatus::Approved->value, ReviewStatus::Rejected->value])]])['status']);

        $review->forceFill(['status' => $status, 'moderated_at' => now()])->save();
        $review->product?->refreshRating();

        return back()->with('toast', ['type' => 'success', 'message' => $status === ReviewStatus::Approved ? 'Avis publié.' : 'Avis refusé.']);
    }

    public function destroy(Review $review): RedirectResponse
    {
        $product = $review->product;
        $review->delete();
        $product?->refreshRating();

        return back()->with('toast', ['type' => 'success', 'message' => 'Avis supprimé.']);
    }
}
