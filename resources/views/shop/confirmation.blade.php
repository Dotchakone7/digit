@extends('layouts.shop')

@section('title', 'Commande confirmée')
@section('robots', 'noindex, nofollow')

@section('content')
    <div class="container-shop max-w-3xl py-12 lg:py-16">
        <div class="card overflow-hidden">
            <div class="bg-gradient-to-br from-sand to-white px-6 py-10 text-center sm:px-10">
                <span class="mx-auto grid size-16 animate-fade-up place-items-center rounded-full bg-success-500 text-white shadow-lg shadow-success-500/30"><x-icon name="check" class="size-8" /></span>
                <h1 class="mt-6 text-3xl font-extrabold">Merci pour votre commande !</h1>
                <p class="mt-2 text-zinc-600">Commande <span class="font-mono font-semibold text-brand-900">{{ $order->number }}</span> enregistrée le {{ $order->created_at->translatedFormat('d F Y à H:i') }}.</p>
                <p class="mt-1 text-sm text-zinc-500">Un récapitulatif a été envoyé à {{ $order->customer_email }}.</p>
            </div>
            <div class="card-body space-y-6 sm:p-10">
                @php($payment = $order->latestPayment)
                <div @class(['flex items-start gap-3 rounded-2xl p-4 text-sm',
                    'bg-success-50 text-success-700' => $order->payment_status === \App\Enums\PaymentStatus::Paid,
                    'bg-info-50 text-info-700' => $order->payment_status !== \App\Enums\PaymentStatus::Paid])>
                    <x-icon :name="$order->payment_status === \App\Enums\PaymentStatus::Paid ? 'check-circle' : 'info'" />
                    <p>
                        @if ($order->payment_status === \App\Enums\PaymentStatus::Paid)
                            Votre paiement a été confirmé. Nous préparons votre commande.
                        @elseif ($payment?->gateway === 'cash_on_delivery')
                            Vous réglerez <strong>{{ money($order->total) }}</strong> à la livraison. Notre équipe vous contactera pour confirmer la commande.
                        @elseif ($order->payment_status === \App\Enums\PaymentStatus::Processing)
                            Votre paiement Mobile Money est en cours de vérification. Vous serez notifié(e) dès sa confirmation.
                        @else
                            Paiement en attente : {{ $order->payment_status->label() }}. <a wire:navigate href="{{ route('payments.show', $order) }}" class="font-semibold underline">Finaliser le paiement</a>
                        @endif
                    </p>
                </div>

                <ul class="divide-y divide-zinc-100">
                    @foreach ($order->items as $item)
                        <li class="flex items-center gap-4 py-3">
                            <span class="size-14 shrink-0 overflow-hidden rounded-xl bg-sand"><x-product-image :src="$item->image_url" :alt="$item->product_name" /></span>
                            <span class="min-w-0 flex-1 text-sm"><span class="block font-medium text-brand-900">{{ $item->product_name }}</span><span class="text-zinc-500">{{ $item->variant_name ? $item->variant_name.' · ' : '' }}Qté {{ $item->quantity }}</span></span>
                            <span class="text-sm font-semibold tabular-nums">{{ money($item->line_total) }}</span>
                        </li>
                    @endforeach
                </ul>
                <dl class="space-y-2 border-t border-zinc-100 pt-4 text-sm">
                    <div class="flex justify-between"><dt class="text-zinc-600">Sous-total</dt><dd class="tabular-nums">{{ money($order->subtotal) }}</dd></div>
                    @if ($order->discount_total)<div class="flex justify-between text-success-700"><dt>Réduction</dt><dd class="tabular-nums">−{{ money($order->discount_total) }}</dd></div>@endif
                    <div class="flex justify-between"><dt class="text-zinc-600">Livraison ({{ $order->shipping_method_name }})</dt><dd class="tabular-nums">{{ $order->shipping_total ? money($order->shipping_total) : 'Offerte' }}</dd></div>
                    <div class="flex justify-between pt-2 text-base font-bold text-brand-900"><dt>Total</dt><dd class="tabular-nums">{{ money($order->total) }}</dd></div>
                </dl>
                <div class="rounded-2xl bg-canvas p-4 text-sm">
                    <p class="font-semibold text-brand-900">Livraison à</p>
                    <p class="mt-1 text-zinc-600">{{ $order->shipping_address['full_name'] ?? '' }} · {{ $order->shipping_address['phone'] ?? '' }}<br>{{ $order->shippingAddressLine() }}</p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <a wire:navigate href="{{ route('account.orders.show', $order) }}" class="btn btn-primary flex-1">Suivre ma commande</a>
                    <a wire:navigate href="{{ route('catalog.index') }}" class="btn btn-secondary flex-1">Continuer mes achats</a>
                </div>
            </div>
        </div>
    </div>
@endsection
