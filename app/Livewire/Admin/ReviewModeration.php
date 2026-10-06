<?php

namespace App\Livewire\Admin;

use App\Enums\ReviewStatus;
use App\Livewire\Concerns\AuthorizesAbility;
use App\Livewire\Concerns\WithTableState;
use App\Models\Review;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class ReviewModeration extends Component
{
    use AuthorizesAbility, WithTableState;

    #[Url(as: 'statut')]
    public string $status = 'pending';

    protected function ability(): string
    {
        return 'reviews.moderate';
    }

    protected function sortable(): array
    {
        return ['created_at', 'rating'];
    }

    public function moderate(int $id, string $decision): void
    {
        $status = ReviewStatus::tryFrom($decision);
        abort_unless(in_array($status, [ReviewStatus::Approved, ReviewStatus::Rejected], true), 422);

        $review = Review::query()->with('product')->findOrFail($id);
        $review->forceFill(['status' => $status, 'moderated_at' => now()])->save();
        $review->product?->refreshRating();

        $this->toast($status === ReviewStatus::Approved ? 'Avis publié.' : 'Avis refusé.');
    }

    public function delete(int $id): void
    {
        $review = Review::query()->with('product')->findOrFail($id);
        $product = $review->product;
        $review->delete();
        $product?->refreshRating();

        $this->toast('Avis supprimé.');
    }

    public function render(): View
    {
        $status = ReviewStatus::tryFrom($this->status) ?? ReviewStatus::Pending;

        return view('livewire.admin.review-moderation', [
            'reviews' => Review::query()->with(['product:id,name,slug', 'user:id,name,email'])
                ->where('status', $status)
                ->when(trim($this->search) !== '', fn ($q) => $q->where(fn ($w) => $w
                    ->whereLike('comment', $this->like(), caseSensitive: false)
                    ->orWhereHas('product', fn ($p) => $p->whereLike('name', $this->like(), caseSensitive: false))))
                ->tap(fn ($q) => $this->applySort($q))
                ->paginate(20),
            'current' => $status,
            'counts' => Review::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }
}
