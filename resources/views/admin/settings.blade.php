@extends('layouts.admin')

@section('title', 'Paramètres')

@php
    $v = fn ($key) => old($key, $values[$key] ?? null);
    $ph = fn ($key) => $fallbacks[$key] ?? null;
@endphp

@section('content')
    <x-admin.page-header title="Paramètres de la boutique" subtitle="Les valeurs laissées vides utilisent celles du fichier .env." />

    <div x-data="{ tab: @js($tab) }">
        <nav class="mb-6 flex flex-wrap gap-1 border-b border-zinc-200" role="tablist">
            @foreach (['general' => 'Contact', 'content' => 'Contenus', 'delivery' => 'Livraison', 'payments' => 'Paiements'] as $key => $label)
                <button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="(tab === '{{ $key }}').toString()" class="-mb-px border-b-2 px-4 py-2.5 text-sm font-medium transition" :class="tab === '{{ $key }}' ? 'border-brand-900 text-zinc-900' : 'border-transparent text-zinc-500 hover:text-zinc-800'">{{ $label }}</button>
            @endforeach
        </nav>

        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="max-w-3xl">
            @csrf @method('PUT')
            <input type="hidden" name="tab" :value="tab">

            <section x-show="tab === 'general'" class="card space-y-4 p-6">
                <h2 class="text-base font-semibold">Coordonnées affichées aux clients</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.input name="contact_phone" label="Téléphone" :value="$v('contact_phone')" :placeholder="$ph('contact_phone')" />
                    <x-form.input name="contact_whatsapp" label="WhatsApp" :value="$v('contact_whatsapp')" :placeholder="$ph('contact_whatsapp')" hint="Format international, ex. +225 07…" />
                    <x-form.input name="contact_email" type="email" label="E-mail" :value="$v('contact_email')" :placeholder="$ph('contact_email')" />
                    <x-form.input name="opening_hours" label="Horaires" :value="$v('opening_hours')" placeholder="Lun–Sam, 8 h – 19 h" />
                </div>
                <x-form.input name="contact_address" label="Adresse" :value="$v('contact_address')" :placeholder="$ph('contact_address')" />
            </section>

            <section x-show="tab === 'content'" x-cloak class="card space-y-4 p-6">
                <h2 class="text-base font-semibold">Page d’accueil & textes</h2>
                <x-form.input name="announcement" label="Bandeau d’annonce (haut de page)" :value="$v('announcement')" maxlength="160" />
                <x-form.input name="hero_eyebrow" label="Accroche du hero" :value="$v('hero_eyebrow')" maxlength="80" placeholder="Nouvelle collection disponible" />
                <x-form.input name="hero_title" label="Titre principal" :value="$v('hero_title')" maxlength="120" placeholder="Découvrez les produits qui vous correspondent." />
                <x-form.textarea name="hero_subtitle" label="Texte du hero" :value="$v('hero_subtitle')" rows="2" maxlength="300" />
                <div>
                    <p class="label">Image du hero</p>
                    @if ($values['hero_image'] ?? null)
                        <img src="{{ \App\Support\Media::url($values['hero_image']) }}" alt="" class="mb-2 h-32 rounded-lg object-cover">
                        <x-form.checkbox name="remove_hero_image" label="Retirer l’image (le produit vedette sera affiché)" />
                    @endif
                    <input type="file" name="hero_image_file" accept="image/jpeg,image/png,image/webp" class="mt-2 block text-sm text-zinc-600 file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-sm file:font-medium">
                    @error('hero_image_file')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <x-form.textarea name="about_text" label="Présentation (pied de page)" :value="$v('about_text')" rows="2" maxlength="400" />
            </section>

            <section x-show="tab === 'delivery'" x-cloak class="card space-y-4 p-6">
                <div>
                    <h2 class="text-base font-semibold">Livreur / plateforme de livraison</h2>
                    <p class="mt-1 text-sm text-zinc-500">Utilisé par le bouton « Contacter un livreur » de chaque commande. Aucun prestataire n’est imposé : renseignez celui avec lequel vous travaillez.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.input name="courier_name" label="Nom du partenaire" :value="$v('courier_name')" :placeholder="$ph('courier_name')" />
                    <x-form.input name="courier_url" type="url" label="URL de la plateforme" :value="$v('courier_url')" :placeholder="$ph('courier_url') ?: 'https://…'" hint="Ouverte directement par le bouton." />
                    <x-form.input name="courier_phone" label="Téléphone du livreur" :value="$v('courier_phone')" :placeholder="$ph('courier_phone')" />
                    <x-form.input name="courier_whatsapp" label="WhatsApp du livreur" :value="$v('courier_whatsapp')" :placeholder="$ph('courier_whatsapp')" hint="Un message pré-rempli (adresse, montant) est proposé." />
                </div>
                <x-form.textarea name="courier_notes" label="Informations complémentaires (visibles par l’équipe)" :value="$v('courier_notes')" rows="2" maxlength="500" />
                <div class="divider"></div>
                <x-form.textarea name="delivery_info" label="Texte de la page « Informations de livraison »" :value="$v('delivery_info')" rows="5" maxlength="3000" hint="Les modes et tarifs sont ajoutés automatiquement depuis « Livraisons »." />
            </section>

            <section x-show="tab === 'payments'" x-cloak class="card space-y-4 p-6">
                <h2 class="text-base font-semibold">Moyens de paiement</h2>
                <p class="text-sm text-zinc-500">Pour des raisons de sécurité, les moyens de paiement et leurs clés se configurent dans le fichier <code class="rounded bg-zinc-100 px-1">.env</code> du serveur (<code class="rounded bg-zinc-100 px-1">PAYMENT_GATEWAYS</code>, <code class="rounded bg-zinc-100 px-1">MOMO_*</code>). Voir le README.</p>
                <ul class="divide-y divide-zinc-100 rounded-xl ring-1 ring-zinc-200">
                    @foreach ($gateways as $code => $gateway)
                        <li class="flex items-center justify-between p-3 text-sm">
                            <span><span class="font-medium text-zinc-900">{{ $gateway['label'] }}</span> <span class="font-mono text-xs text-zinc-500">{{ $code }}</span></span>
                            @if ($gateway['available'])<span class="badge badge-success">Actif</span>
                            @elseif ($gateway['enabled'])<span class="badge badge-warning">Activé mais incomplet</span>
                            @else<span class="badge badge-neutral">Désactivé</span>@endif
                        </li>
                    @endforeach
                </ul>
            </section>

            <div class="mt-6 flex justify-end" x-show="tab !== 'payments'"><button class="btn btn-primary" data-loading="Enregistrement…">Enregistrer</button></div>
        </form>
    </div>
@endsection
