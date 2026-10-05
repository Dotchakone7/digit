@extends('layouts.admin')

@section('title', 'Commande '.$order->number)

@php
    $address = $order->shipping_address;
    $actionLabels = [
        'confirmed' => ['Confirmer la commande', 'btn-primary', 'check-circle'],
        'processing' => ['Passer en préparation', 'btn-primary', 'box'],
        'shipped' => ['Marquer comme expédiée', 'btn-primary', 'truck'],
        'delivered' => ['Marquer comme livrée', 'btn-success', 'home'],
        'cancelled' => ['Annuler', 'btn-danger-soft', 'x'],
        'refunded' => ['Marquer remboursée', 'btn-secondary', 'refresh'],
    ];
@endphp

@section('content')
    <x-admin.page-header :title="'Commande '.$order->number" :subtitle="'Passée le '.$order->created_at->translatedFormat('d F Y à H:i').' · '.$order->items->sum('quantity').' article(s)'" :back="route('admin.orders.index')">
        <x-status-badge :status="$order->status" class="px-3 py-1 text-sm" />
    </x-admin.page-header>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <div class="space-y-6">
            {{-- Workflow actions --}}
            @if ($transitions)
                <section class="card p-5">
                    <h2 class="text-base font-semibold">Faire avancer la commande</h2>
                    <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="mt-4" x-data>
                        @csrf
                        <x-form.input name="comment" label="Commentaire (optionnel, visible dans l’historique et l’e-mail client)" maxlength="500" />
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($transitions as $status)
                                @php([$label, $class, $icon] = $actionLabels[$status->value])
                                @if ($status === \App\Enums\OrderStatus::Cancelled)
                                    <button type="submit" form="cancel-order" class="btn {{ $class }}"><x-icon :name="$icon" class="size-4" /> {{ $label }}</button>
                                @else
                                    <button type="submit" name="status" value="{{ $status->value }}" class="btn {{ $class }}"><x-icon :name="$icon" class="size-4" /> {{ $label }}</button>
                                @endif
                            @endforeach
                        </div>
                    </form>
                    <form id="cancel-order" method="POST" action="{{ route('admin.orders.status', $order) }}" class="hidden" data-confirm="La commande sera annulée, le stock remis en vente et le client notifié." data-confirm-title="Annuler la commande {{ $order->number }} ?" data-confirm-label="Annuler la commande">
                        @csrf <input type="hidden" name="status" value="cancelled"><input type="hidden" name="comment" value="Annulée par la boutique">
                    </form>
                    @if ($order->status === \App\Enums\OrderStatus::Pending && $order->payment_method === 'cash_on_delivery')
                        <p class="mt-3 text-xs text-zinc-500">Paiement à la livraison : confirmez après avoir joint le client par téléphone.</p>
                    @endif
                </section>
            @endif

            <section class="card overflow-hidden">
                <h2 class="px-5 py-4 text-base font-semibold">Produits commandés</h2>
                <div class="overflow-x-auto"><table class="table-admin">
                    <thead><tr><th>Produit</th><th class="text-right">Prix unitaire</th><th class="text-right">Qté</th><th class="text-right">Total</th></tr></thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="size-11 shrink-0 overflow-hidden rounded-lg bg-zinc-100"><x-product-image :src="$item->image_url" alt="" /></span>
                                        <div><p class="font-medium text-zinc-900">{{ $item->product_name }}</p><p class="font-mono text-xs text-zinc-500">{{ $item->sku }}{{ $item->variant_name ? ' · '.$item->variant_name : '' }}</p></div>
                                    </div>
                                </td>
                                <td class="text-right tabular-nums">{{ money($item->unit_price) }}</td>
                                <td class="text-right tabular-nums">{{ $item->quantity }}</td>
                                <td class="text-right font-semibold tabular-nums">{{ money($item->line_total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>
                <dl class="space-y-1.5 border-t border-zinc-100 px-5 py-4 text-sm">
                    <div class="flex justify-between"><dt class="text-zinc-500">Sous-total</dt><dd class="tabular-nums">{{ money($order->subtotal) }}</dd></div>
                    @if ($order->discount_total)<div class="flex justify-between text-success-700"><dt>Réduction ({{ $order->coupon_code }})</dt><dd class="tabular-nums">−{{ money($order->discount_total) }}</dd></div>@endif
                    <div class="flex justify-between"><dt class="text-zinc-500">Livraison · {{ $order->shipping_method_name }}</dt><dd class="tabular-nums">{{ money($order->shipping_total) }}</dd></div>
                    <div class="flex justify-between pt-1 text-base font-bold"><dt>Total</dt><dd class="tabular-nums">{{ money($order->total) }}</dd></div>
                </dl>
            </section>

            <section class="card overflow-hidden">
                <h2 class="px-5 py-4 text-base font-semibold">Paiements</h2>
                <div class="overflow-x-auto"><table class="table-admin">
                    <thead><tr><th>Référence</th><th>Moyen</th><th>Détails</th><th>Statut</th><th class="text-right"><span class="sr-only">Actions</span></th></tr></thead>
                    <tbody>
                        @forelse ($order->payments as $payment)
                            <tr>
                                <td><span class="font-mono text-xs">{{ $payment->reference }}</span><span class="block text-xs text-zinc-500">{{ $payment->created_at->format('d/m/Y H:i') }}</span></td>
                                <td>{{ $payment->gatewayLabel() }}</td>
                                <td class="text-xs text-zinc-600">
                                    @if ($payment->meta['operator'] ?? null)Opérateur : {{ ucfirst($payment->meta['operator']) }}<br>@endif
                                    @if ($payment->meta['transaction_id'] ?? null)Transaction : <span class="font-mono">{{ $payment->meta['transaction_id'] }}</span><br>@endif
                                    @if ($payment->meta['payer_phone'] ?? null)Payeur : {{ $payment->meta['payer_phone'] }}<br>@endif
                                    @if ($payment->paid_at)Payé le {{ $payment->paid_at->format('d/m/Y H:i') }}@endif
                                    @if ($payment->failure_reason)<span class="text-danger-700">{{ $payment->failure_reason }}</span>@endif
                                </td>
                                <td><x-status-badge :status="$payment->status" /></td>
                                <td class="text-right">
                                    @can('payments.manage')
                                        @if ($payment->status->isOpen())
                                            <div class="flex justify-end gap-1">
                                                <form method="POST" action="{{ route('admin.payments.confirm', $payment) }}" data-confirm="Confirmez-vous avoir reçu {{ money($payment->amount) }} ({{ $payment->gatewayLabel() }}) ?" data-confirm-title="Confirmer la réception des fonds" data-confirm-label="Oui, fonds reçus" data-confirm-tone="neutral">@csrf<button class="btn btn-success btn-sm">Confirmer</button></form>
                                                <form method="POST" action="{{ route('admin.payments.reject', $payment) }}" data-confirm="Le paiement sera marqué comme échoué. Le client pourra payer à nouveau." data-confirm-title="Rejeter le paiement ?" data-confirm-label="Rejeter">@csrf<button class="btn btn-danger-soft btn-sm">Rejeter</button></form>
                                            </div>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-zinc-500">Aucun paiement initié.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </section>

            @if ($order->notes)
                <section class="card p-5"><h2 class="text-base font-semibold">Instructions du client</h2><p class="mt-2 text-sm whitespace-pre-line text-zinc-700">{{ $order->notes }}</p></section>
            @endif
        </div>

        <div class="space-y-6">
            {{-- Delivery & courier --}}
            <section class="card p-5">
                <h2 class="flex items-center gap-2 text-base font-semibold"><x-icon name="truck" class="size-[18px]" /> Livraison</h2>
                <div class="mt-3 text-sm text-zinc-700">
                    <p class="font-medium text-zinc-900">{{ $address['full_name'] ?? $order->customer_name }}</p>
                    <p><a href="tel:{{ preg_replace('/[^0-9+]/', '', $address['phone'] ?? '') }}" class="hover:underline">{{ $address['phone'] ?? '' }}</a></p>
                    <p>{{ $order->shippingAddressLine() }}</p>
                    @if (! empty($address['landmark']))<p class="text-zinc-500">Repère : {{ $address['landmark'] }}</p>@endif
                    <p class="mt-2 text-xs font-medium text-zinc-500">{{ $order->shipping_method_name }}</p>
                </div>

                <div class="mt-4 rounded-xl bg-accent-50 p-4 ring-1 ring-accent-200">
                    @if ($courierOptions)
                        <a href="{{ route('admin.orders.courier', $order) }}" target="_blank" rel="noopener" class="btn btn-accent w-full"><x-icon name="truck" class="size-4" /> Contacter un livreur</a>
                        <p class="mt-2 text-center text-xs text-accent-900">{{ $courier->name() ?: 'Plateforme de livraison configurée' }}</p>
                        @if (count($courierOptions) > 1)
                            <div class="mt-3 flex flex-wrap justify-center gap-2">
                                @foreach ($courierOptions as $option)
                                    @continue($option['type'] === 'platform')
                                    <a href="{{ $option['url'] }}" @if ($option['type'] === 'whatsapp') target="_blank" rel="noopener" @endif class="btn btn-secondary btn-sm"><x-icon :name="$option['type'] === 'whatsapp' ? 'whatsapp' : 'phone'" class="size-4" /> {{ $option['label'] }}</a>
                                @endforeach
                            </div>
                        @endif
                        @if (setting('courier_notes'))<p class="mt-3 text-xs text-zinc-600">{{ setting('courier_notes') }}</p>@endif
                    @else
                        <a href="{{ route('admin.orders.courier', $order) }}" class="btn btn-accent w-full"><x-icon name="truck" class="size-4" /> Contacter un livreur</a>
                        <p class="mt-2 text-center text-xs text-accent-900">Aucun livreur configuré — le bouton ouvre les paramètres de livraison.</p>
                    @endif
                </div>

                @foreach ($order->shipments as $shipment)
                    <div class="mt-4 rounded-xl bg-zinc-50 p-3 text-sm">
                        <div class="flex items-center justify-between"><span class="font-medium">{{ $shipment->carrier }}</span><x-status-badge :status="$shipment->status" /></div>
                        @if ($shipment->tracking_number)<p class="mt-1 font-mono text-xs text-zinc-600">Suivi : {{ $shipment->tracking_number }}</p>@endif
                        @if ($shipment->notes)<p class="mt-1 text-xs text-zinc-500">{{ $shipment->notes }}</p>@endif
                    </div>
                @endforeach

                @if (! in_array($order->status, [\App\Enums\OrderStatus::Cancelled, \App\Enums\OrderStatus::Refunded, \App\Enums\OrderStatus::Delivered], true))
                    <details class="group mt-4">
                        <summary class="cursor-pointer text-sm font-medium text-brand-700 hover:underline">+ Enregistrer une expédition</summary>
                        <form method="POST" action="{{ route('admin.orders.shipment', $order) }}" class="mt-3 space-y-3">
                            @csrf
                            <x-form.input name="carrier" label="Livreur / transporteur" :value="setting('courier_name')" required maxlength="60" />
                            <x-form.input name="tracking_number" label="N° de suivi (optionnel)" maxlength="120" />
                            <x-form.input name="notes" label="Note (optionnel)" maxlength="500" />
                            <button class="btn btn-secondary w-full">Enregistrer</button>
                        </form>
                    </details>
                @endif
            </section>

            <section class="card p-5">
                <h2 class="flex items-center gap-2 text-base font-semibold"><x-icon name="user" class="size-[18px]" /> Client</h2>
                <div class="mt-3 space-y-1 text-sm text-zinc-700">
                    <p class="font-medium text-zinc-900">{{ $order->customer_name }}</p>
                    <p><a href="mailto:{{ $order->customer_email }}" class="hover:underline">{{ $order->customer_email }}</a></p>
                    <p><a href="tel:{{ preg_replace('/[^0-9+]/', '', $order->customer_phone) }}" class="hover:underline">{{ $order->customer_phone }}</a></p>
                    @if ($order->user && auth()->user()->can('customers.view'))
                        <a href="{{ route('admin.users.show', $order->user) }}" class="mt-2 inline-block text-sm font-medium text-brand-700 hover:underline">Voir la fiche client →</a>
                    @endif
                </div>
            </section>

            <section class="card p-5">
                <h2 class="mb-4 text-base font-semibold">Suivi de la commande</h2>
                <x-order-timeline :order="$order" />
            </section>

            <section class="card p-5">
                <h2 class="text-base font-semibold">Historique des statuts</h2>
                <ol class="mt-4 space-y-3 border-l border-zinc-200 pl-4">
                    @foreach ($order->statusHistories->reverse() as $history)
                        <li class="relative text-sm">
                            <span class="absolute top-1.5 -left-[21px] size-2.5 rounded-full bg-brand-900 ring-4 ring-white"></span>
                            <p class="font-medium text-zinc-900">{{ $history->from_status ? $history->from_status->label().' → ' : '' }}{{ $history->to_status->label() }}</p>
                            <p class="text-xs text-zinc-500">{{ $history->created_at->format('d/m/Y H:i') }} · {{ $history->user?->name ?? 'Système' }}</p>
                            @if ($history->comment)<p class="mt-0.5 text-xs text-zinc-600 italic">« {{ $history->comment }} »</p>@endif
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>
    </div>
@endsection
