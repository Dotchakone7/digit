@extends('layouts.account')

@section('title', 'Demande de retour')

@section('account')
    <a href="{{ route('account.orders.show', $order) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-500 hover:text-brand-900"><x-icon name="arrow-left" class="size-4" /> Commande {{ $order->number }}</a>
    <h1 class="mt-2 text-2xl font-extrabold sm:text-3xl">Demande de retour</h1>
    <p class="mt-1 text-zinc-500">Délai : {{ config('shop.orders.return_window_days') }} jours après la livraison. <a class="link" href="{{ route('pages.show', 'remboursement') }}">Voir la politique de retour</a></p>
    <form method="POST" action="{{ route('account.returns.store', $order) }}" class="mt-6 card card-body max-w-2xl space-y-5">
        @csrf
        <x-form.select name="reason" label="Motif du retour" :options="$reasons" placeholder="Sélectionnez un motif" required />
        <x-form.textarea name="details" label="Précisions (optionnel)" rows="4" maxlength="2000" placeholder="Décrivez le problème rencontré…" />
        <div class="flex justify-end"><button type="submit" class="btn btn-primary" data-loading="Envoi…">Envoyer la demande</button></div>
    </form>
@endsection
