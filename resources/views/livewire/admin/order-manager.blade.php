{{-- Refreshes every 30 s so a payment confirmed by webhook shows up without reloading. --}}
<div wire:poll.30s.visible>
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

    <x-admin.page-header :title="'Commande '.$order->number" :subtitle="'Passée le '.$order->created_at->translatedFormat('d F Y à H:i').' · '.$order->items->sum('quantity').' article(s)'" :back="route('admin.orders.index')">
        <x-status-badge :status="$order->status" class="px-3 py-1 text-sm" />
    </x-admin.page-header>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <div class="space-y-6">
            {{-- Workflow actions --}}
            @if ($transitions)
                <section class="card p-5">
                    <h2 class="text-base font-semibold">Faire avancer la commande</h2>
                    <div class="mt-4">
                        <x-form.input name="comment" wire:model="comment" label="Commentaire (optionnel, visible dans l’historique et l’e-mail client)" maxlength="500" />
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($transitions as $status)
                                @php([$label, $class, $icon] = $actionLabels[$status->value])
                                @if ($status === \App\Enums\OrderStatus::Cancelled)
                                    <button type="button" class="btn {{ $class }}" wire:loading.attr="disabled" wire:target="changeStatus"
                                            @click="if (await $store.confirm.ask({ title: @js('Annuler la commande '.$order->number.' ?'), message: 'La commande sera annulée, le stock remis en vente et le client notifié.', confirmLabel: 'Annuler la commande' })) $wire.changeStatus('cancelled')"><x-icon :name="$icon" class="size-4" /> {{ $label }}</button>
                                @else
                                    <button type="button" class="btn {{ $class }}" wire:click="changeStatus('{{ $status->value }}')" wire:loading.attr="disabled" wire:target="changeStatus">
                                        <x-icon :name="$icon" class="size-4" wire:loading.remove wire:target="changeStatus('{{ $status->value }}')" />
                                        <span class="spinner size-4" wire:loading wire:target="changeStatus('{{ $status->value }}')"></span>
                                        {{ $label }}
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </div>
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
                <ul class="divide-y divide-zinc-100 border-t border-zinc-100">
                    @forelse ($order->payments as $payment)
                        <li wire:key="payment-{{ $payment->id }}-{{ $payment->status->value }}" class="flex flex-wrap items-start justify-between gap-3 px-5 py-4">
                            <div class="min-w-0 text-sm">
                                <p class="flex flex-wrap items-center gap-2 font-medium text-zinc-900">{{ $payment->gatewayLabel() }} <x-status-badge :status="$payment->status" /></p>
                                <p class="mt-0.5 font-mono text-xs text-zinc-500">{{ $payment->reference }} · {{ $payment->created_at->format('d/m/Y H:i') }} · {{ money($payment->amount) }}</p>
                                <p class="mt-1 text-xs text-zinc-600">
                                    @if ($payment->meta['operator'] ?? null)Opérateur : {{ ucfirst($payment->meta['operator']) }} · @endif
                                    @if ($payment->meta['transaction_id'] ?? null)Transaction : <span class="font-mono">{{ $payment->meta['transaction_id'] }}</span> · @endif
                                    @if ($payment->meta['payer_phone'] ?? null)Payeur : {{ $payment->meta['payer_phone'] }} · @endif
                                    @if ($payment->paid_at)Payé le {{ $payment->paid_at->format('d/m/Y H:i') }}@endif
                                    @if ($payment->failure_reason)<span class="text-danger-700">{{ $payment->failure_reason }}</span>@endif
                                </p>
                            </div>
                            @can('payments.manage')
                                @if ($payment->status->isOpen())
                                    <div class="flex shrink-0 gap-2">
                                        <button type="button" class="btn btn-success btn-sm" wire:loading.attr="disabled" wire:target="confirmPayment,rejectPayment"
                                                @click="if (await $store.confirm.ask({ title: 'Confirmer la réception des fonds', message: @js('Confirmez-vous avoir reçu '.money($payment->amount).' ('.$payment->gatewayLabel().') ?'), confirmLabel: 'Oui, fonds reçus', danger: false })) $wire.confirmPayment({{ $payment->id }})">Confirmer</button>
                                        <button type="button" class="btn btn-danger-soft btn-sm" wire:loading.attr="disabled" wire:target="confirmPayment,rejectPayment"
                                                @click="if (await $store.confirm.ask({ title: 'Rejeter le paiement ?', message: 'Le paiement sera marqué comme échoué. Le client pourra payer à nouveau.', confirmLabel: 'Rejeter' })) $wire.rejectPayment({{ $payment->id }})">Rejeter</button>
                                    </div>
                                @endif
                            @endcan
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-zinc-500">Aucun paiement initié.</li>
                    @endforelse
                </ul>
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
                    <div wire:key="shipment-{{ $shipment->id }}" class="animate-fade-up mt-4 rounded-xl bg-zinc-50 p-3 text-sm">
                        <div class="flex items-center justify-between"><span class="font-medium">{{ $shipment->carrier }}</span><x-status-badge :status="$shipment->status" /></div>
                        @if ($shipment->tracking_number)<p class="mt-1 font-mono text-xs text-zinc-600">Suivi : {{ $shipment->tracking_number }}</p>@endif
                        @if ($shipment->notes)<p class="mt-1 text-xs text-zinc-500">{{ $shipment->notes }}</p>@endif
                    </div>
                @endforeach

                @if (! in_array($order->status, [\App\Enums\OrderStatus::Cancelled, \App\Enums\OrderStatus::Refunded, \App\Enums\OrderStatus::Delivered], true))
                    <div class="mt-4" x-data="{ open: false }" x-on:shipment-saved.window="open = false">
                        <button type="button" class="text-sm font-medium text-brand-700 hover:underline" x-on:click="open = ! open" x-bind:aria-expanded="open">+ Enregistrer une expédition</button>
                        <form wire:submit="addShipment" class="mt-3 space-y-3" x-show="open" x-collapse x-cloak>
                            <x-form.input name="carrier" wire:model="carrier" label="Livreur / transporteur" required maxlength="60" />
                            <x-form.input name="trackingNumber" wire:model="trackingNumber" label="N° de suivi (optionnel)" maxlength="120" />
                            <x-form.input name="shipmentNotes" wire:model="shipmentNotes" label="Note (optionnel)" maxlength="500" />
                            <button class="btn btn-secondary w-full" wire:loading.attr="disabled" wire:target="addShipment"><span class="spinner size-4" wire:loading wire:target="addShipment"></span> Enregistrer</button>
                        </form>
                    </div>
                @endif
            </section>

            <section class="card p-5">
                <h2 class="flex items-center gap-2 text-base font-semibold"><x-icon name="user" class="size-[18px]" /> Client</h2>
                <div class="mt-3 space-y-1 text-sm text-zinc-700">
                    <p class="font-medium text-zinc-900">{{ $order->customer_name }}</p>
                    <p><a href="mailto:{{ $order->customer_email }}" class="hover:underline">{{ $order->customer_email }}</a></p>
                    <p><a href="tel:{{ preg_replace('/[^0-9+]/', '', $order->customer_phone) }}" class="hover:underline">{{ $order->customer_phone }}</a></p>
                    @if ($order->user && auth()->user()->can('customers.view'))
                        <a wire:navigate href="{{ route('admin.users.show', $order->user) }}" class="mt-2 inline-block text-sm font-medium text-brand-700 hover:underline">Voir la fiche client →</a>
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
                        <li wire:key="history-{{ $history->id }}" class="relative text-sm">
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
</div>
