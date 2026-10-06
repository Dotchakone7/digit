@extends('layouts.auth')

@section('title', 'Connexion')
@section('aside_title', 'Heureux de vous revoir.')

@section('form')
    <h1 class="text-2xl font-extrabold sm:text-3xl">Connexion</h1>
    <p class="mt-2 text-sm text-zinc-500">Pas encore de compte ? <a wire:navigate href="{{ route('register') }}" class="link">Créer un compte</a></p>

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5" novalidate>
        @csrf
        <x-form.input name="email" type="email" label="Adresse e-mail" icon="mail" required autocomplete="email" autofocus />
        <div x-data="{ show: false }">
            <div class="mb-1.5 flex items-center justify-between">
                <label for="password" class="label mb-0">Mot de passe</label>
                <a wire:navigate href="{{ route('password.request') }}" class="text-xs font-medium text-brand-700 hover:underline">Mot de passe oublié ?</a>
            </div>
            <div class="relative">
                <input id="password" name="password" :type="show ? 'text' : 'password'" required autocomplete="current-password" class="input pr-12 @error('password') input-error @enderror">
                <button type="button" class="absolute inset-y-0 right-0 grid w-11 place-items-center text-zinc-400 hover:text-brand-900" @click="show = !show" :aria-label="show ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"><x-icon name="eye" class="size-[18px]" /></button>
            </div>
            @error('password')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <label class="flex items-center gap-2.5 text-sm text-zinc-600"><input type="checkbox" name="remember" value="1" class="checkbox"> Rester connecté(e)</label>
        <button type="submit" class="btn btn-primary btn-lg w-full" data-loading="Connexion…">Se connecter</button>
    </form>
@endsection
