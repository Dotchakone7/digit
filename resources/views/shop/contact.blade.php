@extends('layouts.shop')

@section('title', 'Nous contacter')
@section('meta_description', 'Contactez le service client de '.config('shop.name').' par téléphone, WhatsApp ou e-mail.')

@section('content')
    <div class="container-shop py-12">
        <x-breadcrumbs :items="['Contact' => null]" />
        <div class="mt-6 max-w-2xl">
            <h1 class="text-3xl font-extrabold sm:text-4xl">Nous sommes là pour vous aider</h1>
            <p class="mt-3 text-zinc-600">Une question sur un produit, une commande ou une livraison ? Notre équipe vous répond rapidement.</p>
        </div>
        @php
            $channels = array_filter([
                setting('contact_phone') ? ['phone', 'Téléphone', setting('contact_phone'), 'tel:'.preg_replace('/[^0-9+]/', '', setting('contact_phone'))] : null,
                setting('contact_whatsapp') ? ['whatsapp', 'WhatsApp', setting('contact_whatsapp'), 'https://wa.me/'.preg_replace('/\D/', '', setting('contact_whatsapp'))] : null,
                setting('contact_email') ? ['mail', 'E-mail', setting('contact_email'), 'mailto:'.setting('contact_email')] : null,
                setting('contact_address') ? ['map-pin', 'Adresse', setting('contact_address'), null] : null,
            ]);
        @endphp
        @if ($channels)
            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($channels as [$icon, $label, $value, $href])
                    <div class="card card-body">
                        <span class="grid size-11 place-items-center rounded-2xl bg-sand text-brand-800"><x-icon :name="$icon" /></span>
                        <p class="mt-4 text-sm text-zinc-500">{{ $label }}</p>
                        @if ($href)
                            <a href="{{ $href }}" class="mt-1 block font-semibold break-words text-brand-900 hover:underline" @if (str_starts_with($href, 'http')) target="_blank" rel="noopener" @endif>{{ $value }}</a>
                        @else
                            <p class="mt-1 font-semibold text-brand-900">{{ $value }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
            @if ($hours = setting('opening_hours'))
                <p class="mt-6 flex items-center gap-2 text-sm text-zinc-600"><x-icon name="clock" class="size-4" /> {{ $hours }}</p>
            @endif
        @else
            <div class="mt-10 card"><x-empty-state icon="headset" title="Coordonnées bientôt disponibles" text="Les informations de contact n’ont pas encore été renseignées." /></div>
        @endif
    </div>
@endsection
