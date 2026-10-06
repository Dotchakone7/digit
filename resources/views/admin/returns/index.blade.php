@extends('layouts.admin')

@section('title', 'Retours')

@section('content')
    <x-admin.page-header title="Retours & remboursements" subtitle="Demandé → Accepté → Produit reçu → Remboursé (ou Refusé)." />
    <nav class="mb-4 flex flex-wrap gap-1">
        <a wire:navigate href="{{ route('admin.returns.index') }}" @class(['rounded-lg px-3 py-1.5 text-sm font-medium', 'bg-brand-900 text-white' => ! $status, 'text-zinc-600 hover:bg-white' => $status])>Tous</a>
        @foreach (\App\Enums\ReturnStatus::cases() as $s)
            <a wire:navigate href="{{ route('admin.returns.index', ['status' => $s->value]) }}" @class(['rounded-lg px-3 py-1.5 text-sm font-medium', 'bg-brand-900 text-white' => $status === $s->value, 'text-zinc-600 hover:bg-white' => $status !== $s->value])>{{ $s->label() }}</a>
        @endforeach
    </nav>
    <div class="space-y-3">
        @forelse ($returns as $return)
            <article class="card p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-zinc-900">{{ $return->reasonLabel() }} · <a wire:navigate href="{{ route('admin.orders.show', $return->order->number) }}" class="font-mono text-brand-700 hover:underline">{{ $return->order->number }}</a></p>
                        <p class="text-xs text-zinc-500">{{ $return->user?->name ?? $return->order->customer_name }} · {{ $return->created_at->format('d/m/Y H:i') }} · commande de {{ money($return->order->total) }}</p>
                    </div>
                    <x-status-badge :status="$return->status" />
                </div>
                @if ($return->details)<p class="mt-3 text-sm text-zinc-700">{{ $return->details }}</p>@endif
                @if ($return->admin_note)<p class="mt-2 rounded-lg bg-zinc-50 p-2 text-xs text-zinc-600">Note interne : {{ $return->admin_note }}</p>@endif
                @if ($return->refund_amount)<p class="mt-2 text-sm font-medium text-success-700">Remboursé : {{ money($return->refund_amount) }}</p>@endif
                @if ($return->status->allowedTransitions())
                    <form method="POST" action="{{ route('admin.returns.update', $return) }}" class="mt-4 flex flex-wrap items-end gap-2" x-data="{ s: '' }">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" :value="s">
                        <input name="admin_note" placeholder="Note interne (optionnel)" class="input max-w-xs" maxlength="1000" aria-label="Note interne">
                        @if (in_array(\App\Enums\ReturnStatus::Refunded, $return->status->allowedTransitions(), true))
                            <input name="refund_amount" type="number" min="0" placeholder="Montant ({{ money($return->order->total) }})" class="input w-48" aria-label="Montant remboursé">
                        @endif
                        @foreach ($return->status->allowedTransitions() as $next)
                            <button type="submit" @click="s = '{{ $next->value }}'" @class(['btn btn-sm', 'btn-danger-soft' => $next === \App\Enums\ReturnStatus::Rejected, 'btn-primary' => $next !== \App\Enums\ReturnStatus::Rejected])>{{ ['approved' => 'Accepter', 'rejected' => 'Refuser', 'received' => 'Produit reçu', 'refunded' => 'Marquer remboursé'][$next->value] }}</button>
                        @endforeach
                    </form>
                @endif
            </article>
        @empty
            <div class="card"><x-empty-state icon="refresh" title="Aucune demande de retour" /></div>
        @endforelse
    </div>
    <div class="mt-6">{{ $returns->links() }}</div>
@endsection
