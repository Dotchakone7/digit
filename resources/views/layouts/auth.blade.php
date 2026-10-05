@extends('layouts.shop')

@section('robots', 'noindex, follow')

@section('content')
    <div class="container-shop py-10 lg:py-16">
        <div class="mx-auto grid max-w-5xl overflow-hidden rounded-[2rem] bg-white shadow-[var(--shadow-lift)] ring-1 ring-zinc-900/5 lg:grid-cols-2">
            <div class="relative hidden overflow-hidden bg-brand-900 p-12 text-white lg:flex lg:flex-col lg:justify-between">
                <div class="pointer-events-none absolute -top-24 -right-24 size-80 rounded-full bg-accent-500/25 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-32 -left-20 size-80 rounded-full bg-brand-500/30 blur-3xl"></div>
                <x-logo variant="light" class="relative" />
                <div class="relative">
                    <h2 class="text-3xl leading-tight font-extrabold text-white">@yield('aside_title', 'Votre boutique, où que vous soyez.')</h2>
                    <ul class="mt-8 space-y-4 text-brand-100">
                        <li class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-xl bg-white/10"><x-icon name="box" class="size-4" /></span> Suivez vos commandes en temps réel</li>
                        <li class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-xl bg-white/10"><x-icon name="heart" class="size-4" /></span> Retrouvez vos favoris sur tous vos appareils</li>
                        <li class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-xl bg-white/10"><x-icon name="truck" class="size-4" /></span> Commandez plus vite grâce à vos adresses</li>
                    </ul>
                </div>
                <p class="relative text-sm text-brand-300">Paiement sécurisé · Mobile Money · Livraison rapide</p>
            </div>
            <div class="p-6 sm:p-10 lg:p-12">
                @yield('form')
            </div>
        </div>
    </div>
@endsection
