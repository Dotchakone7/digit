<div>
    @if ($toVerify)
        <button type="button" wire:click="$set('status', 'processing')" class="mb-4 flex w-full items-center gap-3 rounded-xl bg-info-50 p-4 text-left text-sm text-info-700 ring-1 ring-info-500/20 transition hover:bg-info-100">
            <x-icon name="info" /> {{ $toVerify }} transfert(s) Mobile Money à vérifier sur votre relevé opérateur — afficher
        </button>
    @endif

    <div class="card mb-4 grid gap-3 p-4 sm:grid-cols-[1fr_220px_220px]">
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-400" />
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Référence, n° de commande, client…" class="input pl-9" aria-label="Rechercher">
        </div>
        <x-form.select name="status" wire:model.live="status" :options="collect(\App\Enums\PaymentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])" placeholder="Tous statuts" aria-label="Statut" />
        <x-form.select name="gateway" wire:model.live="gateway" :options="collect(config('payments.gateways'))->map(fn ($g) => $g['label'])" placeholder="Tous moyens" aria-label="Moyen" />
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="search,status,gateway,sortBy,gotoPage,nextPage,previousPage">
            <table class="table-admin">
                <thead>
                    <tr>
                        <x-admin.th-sort field="created_at" :sort-field="$sortField" :sort-direction="$sortDirection">Commande</x-admin.th-sort>
                        <th>Moyen & transaction</th>
                        <x-admin.th-sort field="amount" :sort-field="$sortField" :sort-direction="$sortDirection" align="right">Montant</x-admin.th-sort>
                        <th>Statut</th>
                        <th class="text-right"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr wire:key="payment-{{ $payment->id }}-{{ $payment->status->value }}">
                            <td><a wire:navigate href="{{ route('admin.orders.show', $payment->order->number) }}" class="font-mono text-xs font-semibold text-brand-800 hover:underline">{{ $payment->order->number }}</a><span class="block text-xs text-zinc-500">{{ $payment->order->customer_name }}</span><span class="block font-mono text-[11px] text-zinc-500">{{ $payment->reference }} · {{ $payment->created_at->format('d/m/Y H:i') }}</span></td>
                            <td>{{ $payment->gatewayLabel() }}@if ($payment->meta['transaction_id'] ?? null)<span class="block text-xs text-zinc-600">{{ ucfirst($payment->meta['operator'] ?? '') }} · <span class="font-mono">{{ $payment->meta['transaction_id'] }}</span></span>@endif</td>
                            <td class="text-right font-semibold tabular-nums">{{ money($payment->amount) }}</td>
                            <td><x-status-badge :status="$payment->status" /></td>
                            <td class="text-right">
                                @if ($payment->status->isOpen())
                                    <div class="flex flex-col items-end gap-1 xl:flex-row xl:justify-end">
                                        <button type="button" class="btn btn-success btn-sm" wire:loading.attr="disabled" wire:target="confirm({{ $payment->id }})"
                                                @click="if (await $store.confirm.ask({ title: 'Confirmer la réception des fonds', message: @js('Confirmez-vous avoir reçu '.money($payment->amount).' ('.$payment->gatewayLabel().') ?'), confirmLabel: 'Fonds reçus', danger: false })) $wire.confirm({{ $payment->id }})">Confirmer</button>
                                        <button type="button" class="btn btn-danger-soft btn-sm" wire:loading.attr="disabled" wire:target="reject({{ $payment->id }})"
                                                @click="if (await $store.confirm.ask({ title: 'Rejeter le paiement ?', message: 'Le paiement sera marqué comme échoué. Le client pourra payer à nouveau.', confirmLabel: 'Rejeter' })) $wire.reject({{ $payment->id }})">Rejeter</button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state icon="card" title="Aucun paiement" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $payments->links() }}</div>
</div>
