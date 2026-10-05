@extends('layouts.account')

@section('title', 'Mes adresses')

@section('account')
    <div x-data="{ modal: false, editing: null, action: '{{ route('account.addresses.store') }}', method: 'POST',
        form: { label: '', full_name: @js(auth()->user()->name), phone: @js(auth()->user()->phone), city: 'Abidjan', district: '', street: '', landmark: '', is_default: false },
        create() { this.editing = null; this.action = '{{ route('account.addresses.store') }}'; this.method = 'POST'; this.form = { label: '', full_name: @js(auth()->user()->name), phone: @js(auth()->user()->phone), city: 'Abidjan', district: '', street: '', landmark: '', is_default: false }; this.modal = true },
        edit(a, url) { this.editing = a.id; this.action = url; this.method = 'PUT'; this.form = { ...a }; this.modal = true } }"
        @if ($errors->any()) x-init="modal = true" @endif>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-extrabold sm:text-3xl">Mes adresses</h1>
            <button type="button" class="btn btn-primary" @click="create()"><x-icon name="plus" class="size-4" /> Ajouter une adresse</button>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            @forelse ($addresses as $address)
                <div @class(['card card-body flex flex-col', 'ring-2 ring-brand-900' => $address->is_default])>
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-semibold text-brand-900">{{ $address->label ?: 'Adresse' }}</p>
                        @if ($address->is_default)<span class="badge badge-primary">Par défaut</span>@endif
                    </div>
                    <p class="mt-3 flex-1 text-sm leading-relaxed text-zinc-600">{{ $address->full_name }} · {{ $address->phone }}<br>{{ $address->oneLine() }}@if ($address->landmark)<br><span class="text-zinc-500">Repère : {{ $address->landmark }}</span>@endif</p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <button type="button" class="btn btn-secondary btn-sm" @click="edit(@js($address->only(['id', 'label', 'full_name', 'phone', 'city', 'district', 'street', 'landmark', 'is_default'])), '{{ route('account.addresses.update', $address) }}')"><x-icon name="edit" class="size-3.5" /> Modifier</button>
                        @unless ($address->is_default)
                            <form method="POST" action="{{ route('account.addresses.default', $address) }}">@csrf<button class="btn btn-ghost btn-sm">Définir par défaut</button></form>
                        @endunless
                        <form method="POST" action="{{ route('account.addresses.destroy', $address) }}" data-confirm="Cette adresse sera définitivement supprimée." data-confirm-title="Supprimer l’adresse ?" data-confirm-label="Supprimer">
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost btn-sm text-danger-700">Supprimer</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="card md:col-span-2"><x-empty-state icon="map-pin" title="Aucune adresse enregistrée" text="Ajoutez une adresse pour commander plus rapidement." /></div>
            @endforelse
        </div>

        {{-- Modal --}}
        <div x-show="modal" x-cloak class="fixed inset-0 z-[85] flex items-end justify-center p-4 sm:items-center" role="dialog" aria-modal="true" aria-labelledby="address-modal-title" @keydown.escape.window="modal = false">
            <div x-show="modal" x-transition.opacity class="absolute inset-0 bg-brand-950/40" @click="modal = false"></div>
            <form method="POST" :action="action" x-show="modal" x-trap.noscroll="modal" x-transition class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl">
                @csrf
                <input type="hidden" name="_method" :value="method">
                <div class="flex items-center justify-between">
                    <h2 id="address-modal-title" class="text-lg font-bold" x-text="editing ? 'Modifier l’adresse' : 'Nouvelle adresse'"></h2>
                    <button type="button" class="btn-icon" @click="modal = false" aria-label="Fermer"><x-icon name="x" /></button>
                </div>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2"><label class="label" for="a-label">Libellé</label><input id="a-label" name="label" x-model="form.label" class="input" placeholder="Maison, Bureau…" maxlength="60"></div>
                    <div><label class="label" for="a-name">Destinataire *</label><input id="a-name" name="full_name" x-model="form.full_name" class="input" required maxlength="120"></div>
                    <div><label class="label" for="a-phone">Téléphone *</label><input id="a-phone" name="phone" type="tel" x-model="form.phone" class="input" required></div>
                    <div><label class="label" for="a-city">Ville *</label><input id="a-city" name="city" x-model="form.city" class="input" required maxlength="120"></div>
                    <div><label class="label" for="a-district">Commune / quartier</label><input id="a-district" name="district" x-model="form.district" class="input" maxlength="120"></div>
                    <div class="sm:col-span-2"><label class="label" for="a-street">Adresse *</label><input id="a-street" name="street" x-model="form.street" class="input" required maxlength="255"></div>
                    <div class="sm:col-span-2"><label class="label" for="a-landmark">Point de repère</label><input id="a-landmark" name="landmark" x-model="form.landmark" class="input" maxlength="255"></div>
                    <label class="flex items-center gap-2.5 text-sm text-zinc-700 sm:col-span-2"><input type="hidden" name="is_default" value="0"><input type="checkbox" name="is_default" value="1" x-model="form.is_default" class="checkbox"> Adresse par défaut</label>
                </div>
                @if ($errors->any())
                    <ul class="mt-4 space-y-1 rounded-xl bg-danger-50 p-3 text-xs text-danger-700">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                @endif
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="btn btn-secondary" @click="modal = false">Annuler</button>
                    <button type="submit" class="btn btn-primary" data-loading="Enregistrement…">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
@endsection
