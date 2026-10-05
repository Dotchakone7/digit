@extends('layouts.account')

@section('title', 'Commande '.$order->number)

@section('account')
    <a href="{{ route('account.orders.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-500 hover:text-brand-900"><x-icon name="arrow-left" class="size-4" /> Mes commandes</a>
    <div class="mt-2 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold sm:text-3xl">Commande <span class="font-mono">{{ $order->number }}</span></h1>
            <p class="mt-1 text-sm text-zinc-500">Passée le {{ $order->created_at->translatedFormat('d F Y à H:i') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('pay', $order)
                <a href="{{ route('payments.show', $order) }}" class="btn btn-accent"><x-icon name="card" class="size-4" /> Finaliser le paiement</a>
            @endcan
            @can('requestReturn', $order)
                <a href="{{ route('account.returns.create', $order) }}" class="btn btn-secondary"><x-icon name="refresh" class="size-4" /> Demander un retour</a>
            @endcan
            @can('cancel', $order)
                <form method="POST" action="{{ route('account.orders.cancel', $order) }}" data-confirm="Votre commande sera annulée et les articles remis en stock." data-confirm-title="Annuler la commande ?" data-confirm-label="Oui, annuler">
                    @csrf
                    <button type="submit" class="btn btn-danger-soft">Annuler la commande</button>
                </form>
            @endcan
        </div>
    </div>

    @foreach ($order->returnRequests as $return)
        <div class="mt-6 flex items-center justify-between gap-3 rounded-2xl bg-info-50 p-4 text-sm text-info-700">
            <p class="flex items-center gap-2"><x-icon name="refresh" class="size-4" /> Demande de retour ({{ $return->reasonLabel() }}) du {{ $return->created_at->translatedFormat('d M Y') }}</p>
            <x-status-badge :status="$return->status" />
        </div>
    @endforeach

    <div class="mt-6 grid grid-cols-[minmax(0,1fr)] gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
        <div class="space-y-6">
            <section class="card">
                <h2 class="border-b border-zinc-100 px-5 py-4 font-sans text-base font-semibold sm:px-6">Articles</h2>
                <ul class="divide-y divide-zinc-100">
                    @foreach ($order->items as $item)
                        <li class="flex items-center gap-4 px-5 py-4 sm:px-6">
                            <span class="size-16 shrink-0 overflow-hidden rounded-xl bg-sand"><x-product-image :src="$item->image_url" :alt="$item->product_name" /></span>
                            <div class="min-w-0 flex-1 text-sm">
                                @if ($item->product && $item->product->isPublished())
                                    <a href="{{ route('products.show', $item->product) }}" class="font-medium text-brand-900 hover:underline">{{ $item->product_name }}</a>
                                @else
                                    <span class="font-medium text-brand-900">{{ $item->product_name }}</span>
                                @endif
                                <p class="text-zinc-500">{{ $item->variant_name ? $item->variant_name.' · ' : '' }}{{ money($item->unit_price) }} × {{ $item->quantity }}</p>
                            </div>
                            <span class="text-sm font-semibold tabular-nums">{{ money($item->line_total) }}</span>
                        </li>
                    @endforeach
                </ul>
                <dl class="space-y-2 border-t border-zinc-100 px-5 py-4 text-sm sm:px-6">
                    <div class="flex justify-between"><dt class="text-zinc-600">Sous-total</dt><dd class="tabular-nums">{{ money($order->subtotal) }}</dd></div>
                    @if ($order->discount_total)<div class="flex justify-between text-success-700"><dt>Réduction ({{ $order->coupon_code }})</dt><dd class="tabular-nums">−{{ money($order->discount_total) }}</dd></div>@endif
                    <div class="flex justify-between"><dt class="text-zinc-600">Livraison</dt><dd class="tabular-nums">{{ $order->shipping_total ? money($order->shipping_total) : 'Offerte' }}</dd></div>
                    <div class="flex justify-between pt-2 text-base font-bold text-brand-900"><dt>Total</dt><dd class="tabular-nums">{{ money($order->total) }}</dd></div>
                </dl>
            </section>

            <div class="grid gap-6 sm:grid-cols-2">
                <section class="card card-body">
                    <h2 class="flex items-center gap-2 font-sans text-base font-semibold"><x-icon name="map-pin" class="size-[18px]" /> Livraison</h2>
                    <p class="mt-3 text-sm text-zinc-600">{{ $order->shipping_address['full_name'] ?? '' }}<br>{{ $order->shipping_address['phone'] ?? '' }}<br>{{ $order->shippingAddressLine() }}</p>
                    @if (! empty($order->shipping_address['landmark']))<p class="mt-1 text-xs text-zinc-500">Repère : {{ $order->shipping_address['landmark'] }}</p>@endif
                    <p class="mt-3 text-sm font-medium text-brand-900">{{ $order->shipping_method_name }}</p>
                    @if ($order->latestShipment?->tracking_number)<p class="text-xs text-zinc-500">Suivi : {{ $order->latestShipment->tracking_number }}</p>@endif
                </section>
                <section class="card card-body">
                    <h2 class="flex items-center gap-2 font-sans text-base font-semibold"><x-icon name="card" class="size-[18px]" /> Paiement</h2>
                    <p class="mt-3 text-sm text-zinc-600">{{ config("payments.gateways.{$order->payment_method}.label", $order->payment_method) }}</p>
                    <x-status-badge :status="$order->payment_status" class="mt-2" />
                </section>
            </div>
        </div>

        <section class="card card-body self-start">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="font-sans text-base font-semibold">Suivi</h2>
                <x-status-badge :status="$order->status" />
            </div>
            <x-order-timeline :order="$order" />
        </section>
    </div>
@endsection
