@extends('layouts.admin')

@section('title', 'Avis clients')

@section('content')
    <x-admin.page-header title="Avis clients" subtitle="Seuls les avis approuvés sont publiés sur la boutique." />
    <nav class="mb-4 flex gap-1" aria-label="Statut des avis">
        @foreach (\App\Enums\ReviewStatus::cases() as $s)
            <a href="{{ route('admin.reviews.index', ['status' => $s->value]) }}" @class(['rounded-lg px-3 py-1.5 text-sm font-medium', 'bg-brand-900 text-white' => $status === $s, 'text-zinc-600 hover:bg-white' => $status !== $s])>{{ $s->label() }} <span class="font-normal tabular-nums">{{ $counts[$s->value] ?? 0 }}</span></a>
        @endforeach
    </nav>
    <div class="space-y-3">
        @forelse ($reviews as $review)
            <article class="card p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-3"><x-rating :value="$review->rating" /><span class="text-sm font-semibold text-zinc-900">{{ $review->title }}</span></div>
                        <p class="mt-1 text-xs text-zinc-500">{{ $review->user?->name }} ({{ $review->user?->email }}) · {{ $review->created_at->format('d/m/Y') }} · sur <a href="{{ $review->product ? route('products.show', $review->product) : '#' }}" target="_blank" class="font-medium text-brand-700 hover:underline">{{ $review->product?->name }}</a>@if ($review->order_id) · <span class="text-success-700">achat vérifié</span>@endif</p>
                    </div>
                    <div class="flex gap-2">
                        @if ($review->status !== \App\Enums\ReviewStatus::Approved)
                            <form method="POST" action="{{ route('admin.reviews.update', $review) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="approved"><button class="btn btn-success btn-sm"><x-icon name="check" class="size-4" /> Publier</button></form>
                        @endif
                        @if ($review->status !== \App\Enums\ReviewStatus::Rejected)
                            <form method="POST" action="{{ route('admin.reviews.update', $review) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="rejected"><button class="btn btn-secondary btn-sm">Refuser</button></form>
                        @endif
                        <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" data-confirm="L’avis sera définitivement supprimé." data-confirm-title="Supprimer l’avis ?" data-confirm-label="Supprimer">@csrf @method('DELETE')<button class="btn-icon size-8 text-zinc-400 hover:text-danger-600" aria-label="Supprimer"><x-icon name="trash" class="size-4" /></button></form>
                    </div>
                </div>
                <p class="mt-3 text-sm text-zinc-700">{{ $review->comment }}</p>
            </article>
        @empty
            <div class="card"><x-empty-state icon="star" :title="'Aucun avis « '.mb_strtolower($status->label()).' »'" /></div>
        @endforelse
    </div>
    <div class="mt-6">{{ $reviews->links() }}</div>
@endsection
