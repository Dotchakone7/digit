@extends('layouts.shop')

@section('title', 'Statut du paiement')
@section('robots', 'noindex, nofollow')

@php($status = $payment->status)
@section('content')
    <div class="container-shop max-w-xl py-16"
         x-data="{ status: @js($status->value), label: @js($status->label()), final: {{ $status->isFinal() ? 'true' : 'false' }}, tries: 0 }"
         x-init="if (!final) { const t = setInterval(async () => { tries++; try { const r = await fetch(@js(route('payments.status', $payment)), { headers: { Accept: 'application/json' } }); const d = await r.json(); status = d.status; label = d.label; final = d.final; } catch (e) {} if (final || tries > 40) { clearInterval(t); if (status === 'paid') window.location.href = @js(route('checkout.confirmation', $payment->order)); } }, 3000) }">
        <div class="card card-body text-center sm:p-10">
            <template x-if="status === 'paid'">
                <div><span class="mx-auto grid size-16 place-items-center rounded-full bg-success-50 text-success-600"><x-icon name="check-circle" class="size-8" /></span><h1 class="mt-5 text-2xl font-extrabold">Paiement confirmé</h1></div>
            </template>
            <template x-if="['pending','processing'].includes(status)">
                <div><span class="mx-auto grid size-16 place-items-center rounded-full bg-info-50 text-info-600"><span class="spinner size-7"></span></span><h1 class="mt-5 text-2xl font-extrabold">Vérification du paiement…</h1>
                    <p class="mt-2 text-sm text-zinc-500">Nous attendons la confirmation de l’opérateur. Cette page se met à jour automatiquement.</p></div>
            </template>
            <template x-if="['failed','cancelled','expired'].includes(status)">
                <div><span class="mx-auto grid size-16 place-items-center rounded-full bg-danger-50 text-danger-600"><x-icon name="alert" class="size-8" /></span><h1 class="mt-5 text-2xl font-extrabold">Paiement non abouti</h1>
                    <p class="mt-2 text-sm text-zinc-500">Statut : <span x-text="label"></span>. Aucun montant n’a été débité pour cette tentative.</p></div>
            </template>
            <p class="mt-6 text-sm text-zinc-500">Commande <span class="font-mono font-semibold text-brand-900">{{ $payment->order->number }}</span> · {{ money($payment->amount) }}</p>
            <div class="mt-8 flex flex-col gap-2 sm:flex-row sm:justify-center">
                <a x-show="['failed','cancelled','expired'].includes(status)" href="{{ route('payments.show', $payment->order) }}" class="btn btn-primary">Réessayer le paiement</a>
                <a wire:navigate href="{{ route('account.orders.show', $payment->order) }}" class="btn btn-secondary">Voir ma commande</a>
            </div>
        </div>
    </div>
@endsection
