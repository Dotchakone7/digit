@extends('layouts.account')

@section('title', 'Mon profil')

@section('account')
    <h1 class="text-2xl font-extrabold sm:text-3xl">Mon profil</h1>
    <form method="POST" action="{{ route('account.profile.update') }}" class="mt-6 card card-body max-w-2xl space-y-5">
        @csrf @method('PUT')
        <x-form.input name="name" label="Nom complet" :value="$user->name" required autocomplete="name" />
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="email" type="email" label="Adresse e-mail" :value="$user->email" required autocomplete="email" />
            <x-form.input name="phone" type="tel" label="Téléphone" :value="$user->phone" required autocomplete="tel" />
        </div>
        <div class="flex justify-end"><button type="submit" class="btn btn-primary" data-loading="Enregistrement…">Enregistrer</button></div>
    </form>
@endsection
