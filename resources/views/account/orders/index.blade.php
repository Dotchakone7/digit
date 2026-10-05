@extends('layouts.account')

@section('title', 'Mes commandes')

@section('account')
    <h1 class="text-2xl font-extrabold sm:text-3xl">Mes commandes</h1>
    <div class="mt-6 space-y-4">
        @forelse ($orders as $order)
            <a href="{{ route('account.orders.show', $order) }}" class="card block transition hover:shadow-[var(--shadow-lift)]">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-5 py-4">
                    <div class="flex flex-wrap items-center gap-x-6 gap-y-1 text-sm">
                        <span><span class="text-zinc-500">N°</span> <span class="font-mono font-semibold text-brand-900">{{ $order->number }}</span></span>
                        <span class="text-zinc-500">{{ $order->created_at->translatedFormat('d F Y') }}</span>
                        <span class="font-semibold text-brand-900 tabular-nums">{{ money($order->total) }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-status-badge :status="$order->status" />
                        @if ($order->awaitsPayment())<span class="badge badge-warning">Paiement à finaliser</span>@endif
                    </div>
                </div>
                <div class="flex items-center gap-3 px-5 py-4">
                    <div class="flex -space-x-3">
                        @foreach ($order->items->take(4) as $item)
                            <span class="size-12 overflow-hidden rounded-xl bg-sand ring-2 ring-white"><x-product-image :src="$item->image_url" :alt="$item->product_name" /></span>
                        @endforeach
                    </div>
                    <p class="line-clamp-1 flex-1 text-sm text-zinc-600">{{ $order->items->pluck('product_name')->implode(', ') }}</p>
                    <x-icon name="chevron-right" class="size-5 text-zinc-400" />
                </div>
            </a>
        @empty
            <div class="card"><x-empty-state icon="box" title="Vous n’avez pas encore passé de commande" text="Découvrez nos produits et passez votre première commande.">
                <a href="{{ route('catalog.index') }}" class="btn btn-primary">Découvrir le catalogue</a>
            </x-empty-state></div>
        @endforelse
        <div class="pt-4">{{ $orders->links() }}</div>
    </div>
@endsection
