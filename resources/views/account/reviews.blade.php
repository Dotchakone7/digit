@extends('layouts.account')

@section('title', 'Mes avis')

@section('account')
    <h1 class="text-2xl font-extrabold sm:text-3xl">Mes avis</h1>

    @if ($toReview->isNotEmpty())
        <section class="mt-6 card card-body">
            <h2 class="font-sans text-base font-semibold">Donnez votre avis sur vos achats</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($toReview as $product)
                    <a wire:navigate href="{{ route('products.show', $product) }}#avis" class="flex items-center gap-3 rounded-2xl p-3 ring-1 ring-zinc-200 transition hover:ring-brand-900">
                        <span class="size-12 shrink-0 overflow-hidden rounded-xl bg-sand"><x-product-image :src="$product->image_url" :alt="$product->name" /></span>
                        <span class="min-w-0 text-sm"><span class="line-clamp-1 font-medium text-brand-900">{{ $product->name }}</span><span class="text-xs font-semibold text-accent-700">Noter ce produit →</span></span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <div class="mt-6 space-y-4">
        @forelse ($reviews as $review)
            <article class="card card-body">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <a wire:navigate href="{{ $review->product?->isPublished() ? route('products.show', $review->product) : '#' }}" class="flex items-center gap-3">
                        <span class="size-12 overflow-hidden rounded-xl bg-sand"><x-product-image :src="$review->product?->image_url" :alt="$review->product?->name" /></span>
                        <span><span class="block text-sm font-semibold text-brand-900">{{ $review->product?->name }}</span><x-rating :value="$review->rating" size="size-3.5" /></span>
                    </a>
                    <div class="flex items-center gap-2">
                        <x-status-badge :status="$review->status" />
                        <form method="POST" action="{{ route('account.reviews.destroy', $review) }}" data-confirm="Votre avis sera supprimé définitivement." data-confirm-title="Supprimer l’avis ?" data-confirm-label="Supprimer">
                            @csrf @method('DELETE')
                            <button class="btn-icon text-zinc-400 hover:text-danger-600" aria-label="Supprimer l’avis"><x-icon name="trash" class="size-4" /></button>
                        </form>
                    </div>
                </div>
                @if ($review->title)<h3 class="mt-4 font-sans text-sm font-semibold">{{ $review->title }}</h3>@endif
                <p class="mt-1 text-sm text-zinc-600">{{ $review->comment }}</p>
            </article>
        @empty
            <div class="card"><x-empty-state icon="star" title="Vous n’avez pas encore publié d’avis" text="Après la livraison d’une commande, partagez votre expérience." /></div>
        @endforelse
    </div>
@endsection
