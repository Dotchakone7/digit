@extends('layouts.admin')

@section('title', 'Paiements')

@section('content')
    <x-admin.page-header title="Paiements" subtitle="Un paiement n’est confirmé que par le prestataire (webhook vérifié) ou par vérification manuelle." />

    @if ($toVerify)
        <a href="{{ route('admin.payments.index', ['status' => 'processing']) }}" class="mb-4 flex items-center gap-3 rounded-xl bg-info-50 p-4 text-sm text-info-700 ring-1 ring-info-500/20">
            <x-icon name="info" /> {{ $toVerify }} transfert(s) Mobile Money à vérifier sur votre relevé opérateur.
        </a>
    @endif

    <form method="GET" class="card mb-4 grid gap-3 p-4 sm:grid-cols-[1fr_200px_220px_auto]">
        <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Référence paiement ou n° de commande…" class="input" aria-label="Rechercher">
        <x-form.select name="status" :options="collect(\App\Enums\PaymentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])" :value="$filters['status'] ?? ''" placeholder="Tous statuts" aria-label="Statut" />
        <x-form.select name="gateway" :options="collect(config('payments.gateways'))->map(fn ($g) => $g['label'])" :value="$filters['gateway'] ?? ''" placeholder="Tous moyens" aria-label="Moyen" />
        <button class="btn btn-secondary">Filtrer</button>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th>Référence</th><th>Commande</th><th>Moyen</th><th>Transaction</th><th class="text-right">Montant</th><th>Statut</th><th class="text-right"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td><span class="font-mono text-xs">{{ $payment->reference }}</span><span class="block text-xs text-zinc-500">{{ $payment->created_at->format('d/m/Y H:i') }}</span></td>
                            <td><a href="{{ route('admin.orders.show', $payment->order->number) }}" class="font-mono text-xs font-semibold text-brand-800 hover:underline">{{ $payment->order->number }}</a><span class="block text-xs text-zinc-500">{{ $payment->order->customer_name }}</span></td>
                            <td>{{ $payment->gatewayLabel() }}</td>
                            <td class="text-xs text-zinc-600">@if ($payment->meta['transaction_id'] ?? null){{ ucfirst($payment->meta['operator'] ?? '') }} · <span class="font-mono">{{ $payment->meta['transaction_id'] }}</span>@else — @endif</td>
                            <td class="text-right font-semibold tabular-nums">{{ money($payment->amount) }}</td>
                            <td><x-status-badge :status="$payment->status" /></td>
                            <td class="text-right">
                                @if ($payment->status->isOpen())
                                    <div class="flex justify-end gap-1">
                                        <form method="POST" action="{{ route('admin.payments.confirm', $payment) }}" data-confirm="Confirmez-vous avoir reçu {{ money($payment->amount) }} ?" data-confirm-title="Confirmer la réception des fonds" data-confirm-label="Fonds reçus" data-confirm-tone="neutral">@csrf<button class="btn btn-success btn-sm">Confirmer</button></form>
                                        <form method="POST" action="{{ route('admin.payments.reject', $payment) }}" data-confirm="Le paiement sera marqué comme échoué." data-confirm-title="Rejeter le paiement ?" data-confirm-label="Rejeter">@csrf<button class="btn btn-danger-soft btn-sm">Rejeter</button></form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty-state icon="card" title="Aucun paiement" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $payments->links() }}</div>
@endsection
