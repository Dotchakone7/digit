@extends('layouts.account')

@section('title', 'Sécurité')

@section('account')
    <h1 class="text-2xl font-extrabold sm:text-3xl">Sécurité</h1>
    <p class="mt-1 text-zinc-500">Modifiez votre mot de passe. Vos autres sessions seront déconnectées.</p>
    <form method="POST" action="{{ route('account.password.update') }}" class="mt-6 card card-body max-w-2xl space-y-5">
        @csrf @method('PUT')
        <x-form.input name="current_password" type="password" label="Mot de passe actuel" required autocomplete="current-password" />
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="password" type="password" label="Nouveau mot de passe" required autocomplete="new-password" hint="8 caractères min., lettres et chiffres" />
            <x-form.input name="password_confirmation" type="password" label="Confirmation" required autocomplete="new-password" />
        </div>
        <div class="flex justify-end"><button type="submit" class="btn btn-primary" data-loading="Enregistrement…">Mettre à jour</button></div>
    </form>
@endsection
