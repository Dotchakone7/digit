@extends('layouts.account')

@section('title', 'Mon compte')

@section('account')
    <h1 class="text-2xl font-extrabold sm:text-3xl">Bonjour {{ explode(' ', auth()->user()->name)[0] }} 👋</h1>
    <p class="mt-1 text-zinc-500">Retrouvez ici vos commandes, vos adresses et vos favoris.</p>

    <div class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            ['Commandes', $stats['orders'], 'box', route('account.orders.index')],
            ['En cours', $stats['in_progress'], 'truck', route('account.orders.index')],
            ['Favoris', $stats['wishlist'], 'heart', route('account.wishlist')],
            ['Total dépensé', money($stats['spent']), 'wallet', null],
        ] as [$label, $value, $icon, $url])
            <a wire:navigate @if ($url) href="{{ $url }}" @endif class="card card-body block transition hover:shadow-[var(--shadow-lift)]">
                <span class="grid size-10 place-items-center rounded-xl bg-sand text-brand-800"><x-icon :name="$icon" class="size-[18px]" /></span>
                <p class="mt-4 text-xs font-medium text-zinc-500">{{ $label }}</p>
                <p class="mt-0.5 font-display text-xl font-bold text-brand-900 tabular-nums">{{ $value }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-8 grid grid-cols-[minmax(0,1fr)] gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
        <section class="card">
            <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4 sm:px-6">
                <h2 class="font-sans text-base font-semibold">Commandes récentes</h2>
                <a wire:navigate href="{{ route('account.orders.index') }}" class="text-sm font-semibold text-brand-900 hover:underline">Tout voir</a>
            </div>
            @forelse ($recentOrders as $order)
                <a wire:navigate href="{{ route('account.orders.show', $order) }}" class="flex items-center justify-between gap-4 border-b border-zinc-50 px-5 py-4 transition last:border-0 hover:bg-canvas sm:px-6">
                    <div>
                        <p class="font-mono text-sm font-semibold text-brand-900">{{ $order->number }}</p>
                        <p class="text-xs text-zinc-500">{{ $order->created_at->translatedFormat('d M Y') }} · {{ $order->items_count }} article(s)</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-status-badge :status="$order->status" class="hidden sm:inline-flex" />
                        <span class="text-sm font-semibold tabular-nums">{{ money($order->total) }}</span>
                        <x-icon name="chevron-right" class="size-4 text-zinc-400" />
                    </div>
                </a>
            @empty
                <x-empty-state icon="box" title="Aucune commande pour le moment" text="Vos commandes apparaîtront ici.">
                    <a wire:navigate href="{{ route('catalog.index') }}" class="btn btn-primary">Commencer mes achats</a>
                </x-empty-state>
            @endforelse
        </section>

        <section class="card card-body">
            <h2 class="flex items-center gap-2 font-sans text-base font-semibold"><x-icon name="bell" class="size-[18px]" /> Notifications</h2>
            <ul class="mt-4 space-y-4">
                @forelse ($notifications as $notification)
                    <li>
                        <a wire:navigate href="{{ $notification->data['url'] ?? '#' }}" class="block rounded-xl p-2 -m-2 transition hover:bg-canvas">
                            <p class="text-sm font-medium text-brand-900">{{ $notification->data['title'] ?? 'Notification' }}</p>
                            <p class="text-xs text-zinc-500">{{ $notification->created_at->diffForHumans() }}</p>
                        </a>
                    </li>
                @empty
                    <li class="text-sm text-zinc-500">Aucune nouvelle notification.</li>
                @endforelse
            </ul>
        </section>
    </div>
@endsection
