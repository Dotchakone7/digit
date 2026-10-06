<div>
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <nav class="flex gap-1" aria-label="Statut des avis">
            @foreach (\App\Enums\ReviewStatus::cases() as $s)
                <button type="button" wire:key="tab-{{ $s->value }}" wire:click="$set('status', '{{ $s->value }}')" @class(['rounded-lg px-3 py-1.5 text-sm font-medium transition', 'bg-brand-900 text-white' => $current === $s, 'text-zinc-600 hover:bg-white' => $current !== $s])>{{ $s->label() }} <span class="font-normal tabular-nums">{{ $counts[$s->value] ?? 0 }}</span></button>
            @endforeach
        </nav>
        <div class="relative ml-auto w-full sm:w-72">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-400" />
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Produit ou commentaire…" class="input pl-9" aria-label="Rechercher un avis">
        </div>
    </div>

    <div class="space-y-3" wire:loading.class="opacity-60" wire:target="status,search,gotoPage,nextPage,previousPage">
        @forelse ($reviews as $review)
            <article wire:key="review-{{ $review->id }}" class="card animate-fade-up p-5" x-data="{ leaving: false }" x-show="! leaving" x-transition.duration.250ms>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-3"><x-rating :value="$review->rating" /><span class="text-sm font-semibold text-zinc-900">{{ $review->title }}</span></div>
                        <p class="mt-1 text-xs text-zinc-500">{{ $review->user?->name }} ({{ $review->user?->email }}) · {{ $review->created_at->format('d/m/Y') }} · sur <a href="{{ $review->product ? route('products.show', $review->product) : '#' }}" target="_blank" class="font-medium text-brand-700 hover:underline">{{ $review->product?->name }}</a>@if ($review->order_id) · <span class="text-success-700">achat vérifié</span>@endif</p>
                    </div>
                    <div class="flex gap-2">
                        @if ($review->status !== \App\Enums\ReviewStatus::Approved)
                            <button type="button" class="btn btn-success btn-sm" @click="leaving = true; $wire.moderate({{ $review->id }}, 'approved')"><x-icon name="check" class="size-4" /> Publier</button>
                        @endif
                        @if ($review->status !== \App\Enums\ReviewStatus::Rejected)
                            <button type="button" class="btn btn-secondary btn-sm" @click="leaving = true; $wire.moderate({{ $review->id }}, 'rejected')">Refuser</button>
                        @endif
                        <button type="button" class="btn-icon size-8 text-zinc-400 hover:text-danger-600" aria-label="Supprimer"
                                @click="if (await $store.confirm.ask({ title: 'Supprimer l’avis ?', message: 'L’avis sera définitivement supprimé.', confirmLabel: 'Supprimer' })) { leaving = true; $wire.delete({{ $review->id }}) }"><x-icon name="trash" class="size-4" /></button>
                    </div>
                </div>
                <p class="mt-3 text-sm text-zinc-700">{{ $review->comment }}</p>
            </article>
        @empty
            <div class="card"><x-empty-state icon="star" :title="'Aucun avis « '.mb_strtolower($current->label()).' »'" /></div>
        @endforelse
    </div>
    <div class="mt-6">{{ $reviews->links() }}</div>
</div>
