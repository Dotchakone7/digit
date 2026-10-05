@extends('layouts.auth')

@section('title', 'Créer un compte')
@section('aside_title', 'Rejoignez-nous en moins d’une minute.')

@section('form')
    <h1 class="text-2xl font-extrabold sm:text-3xl">Créer un compte</h1>
    <p class="mt-2 text-sm text-zinc-500">Déjà client ? <a href="{{ route('login') }}" class="link">Se connecter</a></p>

    <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5" novalidate>
        @csrf
        <x-form.input name="name" label="Nom complet" icon="user" required autocomplete="name" autofocus maxlength="120" />
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="email" type="email" label="Adresse e-mail" required autocomplete="email" maxlength="190" />
            <x-form.input name="phone" type="tel" label="Téléphone" required autocomplete="tel" placeholder="+225 07 00 00 00 00" />
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="password" type="password" label="Mot de passe" required autocomplete="new-password" hint="8 caractères min., lettres et chiffres" />
            <x-form.input name="password_confirmation" type="password" label="Confirmation" required autocomplete="new-password" />
        </div>
        <label class="flex items-start gap-2.5 text-sm text-zinc-600">
            <input type="checkbox" name="terms" value="1" class="checkbox mt-0.5" @checked(old('terms'))>
            <span>J’accepte les <a href="{{ route('pages.show', 'conditions-generales') }}" target="_blank" class="link">conditions générales</a> et la <a href="{{ route('pages.show', 'confidentialite') }}" target="_blank" class="link">politique de confidentialité</a>.</span>
        </label>
        @error('terms')<p class="field-error -mt-3">{{ $message }}</p>@enderror
        <button type="submit" class="btn btn-primary btn-lg w-full" data-loading="Création…">Créer mon compte</button>
    </form>
@endsection
