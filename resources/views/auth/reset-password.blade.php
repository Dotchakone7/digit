@extends('layouts.auth')

@section('title', 'Nouveau mot de passe')

@section('form')
    <h1 class="text-2xl font-extrabold sm:text-3xl">Nouveau mot de passe</h1>
    <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-form.input name="email" type="email" label="Adresse e-mail" :value="$email" required autocomplete="email" />
        <x-form.input name="password" type="password" label="Nouveau mot de passe" required autocomplete="new-password" hint="8 caractères min., lettres et chiffres" />
        <x-form.input name="password_confirmation" type="password" label="Confirmation" required autocomplete="new-password" />
        <button type="submit" class="btn btn-primary btn-lg w-full" data-loading="Enregistrement…">Réinitialiser</button>
    </form>
@endsection
