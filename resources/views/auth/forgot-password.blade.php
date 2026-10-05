@extends('layouts.auth')

@section('title', 'Mot de passe oublié')

@section('form')
    <h1 class="text-2xl font-extrabold sm:text-3xl">Mot de passe oublié</h1>
    <p class="mt-2 text-sm text-zinc-500">Indiquez votre adresse e-mail : nous vous enverrons un lien pour choisir un nouveau mot de passe.</p>
    @if (session('status'))
        <div class="mt-6 flex gap-3 rounded-2xl bg-success-50 p-4 text-sm text-success-700"><x-icon name="check-circle" /> {{ session('status') }}</div>
    @endif
    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
        @csrf
        <x-form.input name="email" type="email" label="Adresse e-mail" icon="mail" required autocomplete="email" autofocus />
        <button type="submit" class="btn btn-primary btn-lg w-full" data-loading="Envoi…">Envoyer le lien</button>
        <a href="{{ route('login') }}" class="btn btn-ghost w-full"><x-icon name="arrow-left" class="size-4" /> Retour à la connexion</a>
    </form>
@endsection
