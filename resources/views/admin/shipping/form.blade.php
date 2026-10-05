@extends('layouts.admin')

@section('title', $method->exists ? $method->name : 'Nouveau mode de livraison')

@section('content')
    <x-admin.page-header :title="$method->exists ? 'Modifier « '.$method->name.' »' : 'Nouveau mode de livraison'" :back="route('admin.shipping-methods.index')" />
    <form method="POST" action="{{ $method->exists ? route('admin.shipping-methods.update', $method) : route('admin.shipping-methods.store') }}" class="card max-w-3xl space-y-5 p-6">
        @csrf @if ($method->exists) @method('PUT') @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.input name="name" label="Nom" :value="$method->name" required maxlength="120" />
            <x-form.input name="code" label="Code interne" :value="$method->code" maxlength="40" hint="Généré depuis le nom si vide." />
        </div>
        <x-form.input name="description" label="Description" :value="$method->description" maxlength="255" />
        <div class="grid gap-4 sm:grid-cols-3">
            <x-form.input name="price" type="number" min="0" :label="'Tarif ('.config('shop.currency.symbol').')'" :value="\App\Support\Money::toMajor($method->price)" required />
            <x-form.input name="free_over_amount" type="number" min="0" label="Gratuit à partir de" :value="\App\Support\Money::toMajor($method->free_over_amount)" />
            <x-form.input name="estimated_delay" label="Délai estimé" :value="$method->estimated_delay" maxlength="120" placeholder="24 à 72 h" />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.input name="position" type="number" min="0" label="Ordre d’affichage" :value="$method->position" />
            <x-form.checkbox name="is_active" label="Proposé aux clients" :checked="$method->is_active" class="sm:pt-7" />
        </div>
        <div class="flex justify-end"><button class="btn btn-primary" data-loading="Enregistrement…">Enregistrer</button></div>
    </form>
@endsection
