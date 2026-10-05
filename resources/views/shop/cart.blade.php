@extends('layouts.shop')

@section('title', 'Mon panier')
@section('robots', 'noindex, follow')

@section('content')
    <div class="container-shop py-8 lg:py-12" x-data x-init="$store.cart.summary = @js($summary->toArray()); $store.cart.count = {{ $summary->count() }}">
        <x-breadcrumbs :items="['Panier' => null]" />
        <h1 class="mt-4 text-3xl font-extrabold sm:text-4xl">Mon panier</h1>

        <template x-if="$store.cart.summary && $store.cart.summary.items.length === 0">
            <div class="mt-8 card">
                <x-empty-state icon="bag" title="Votre panier est vide" text="Parcourez notre catalogue et ajoutez vos coups de cœur.">
                    <a href="{{ route('catalog.index') }}" class="btn btn-primary btn-lg">Découvrir le catalogue <x-icon name="arrow-right" class="size-4" /></a>
                </x-empty-state>
            </div>
        </template>

        <div class="mt-8 grid grid-cols-[minmax(0,1fr)] gap-8 lg:grid-cols-[minmax(0,1fr)_380px]" x-show="$store.cart.summary?.items.length">
            <div class="card overflow-hidden">
                <ul class="divide-y divide-zinc-100">
                    <template x-for="item in $store.cart.summary?.items ?? []" :key="item.id + '-' + item.quantity">
                        <li class="flex gap-4 p-4 sm:gap-6 sm:p-6" x-data="cartLine(item.id, item.quantity, item.max)" :class="busy && 'opacity-60 pointer-events-none'">
                            <a :href="item.url" class="size-24 shrink-0 overflow-hidden rounded-2xl bg-sand sm:size-28">
                                <img x-show="item.image" :src="item.image" :alt="item.name" class="size-full object-cover">
                            </a>
                            <div class="flex min-w-0 flex-1 flex-col">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <a :href="item.url" class="line-clamp-2 font-semibold text-brand-900 hover:underline" x-text="item.name"></a>
                                        <p class="mt-0.5 text-sm text-zinc-500" x-show="item.variant" x-text="item.variant"></p>
                                        <p class="mt-1 text-sm text-zinc-500"><span x-text="item.unit_price_formatted"></span> / unité</p>
                                    </div>
                                    <button type="button" class="btn-icon -mt-2 -mr-2 text-zinc-400 hover:text-danger-600" @click="remove()" aria-label="Retirer l’article"><x-icon name="trash" class="size-[18px]" /></button>
                                </div>
                                <p class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-danger-700" x-show="!item.available">
                                    <x-icon name="alert" class="size-4" /> Stock insuffisant : réduisez la quantité (max. <span x-text="item.max"></span>).
                                </p>
                                <div class="mt-auto flex items-center justify-between gap-3 pt-3">
                                    <div class="inline-flex items-center rounded-full bg-white ring-1 ring-zinc-200">
                                        <button type="button" class="grid size-10 place-items-center rounded-full text-zinc-600 hover:bg-zinc-50" @click="set(quantity - 1)" aria-label="Diminuer la quantité"><x-icon name="minus" class="size-4" /></button>
                                        <span class="w-8 text-center font-semibold tabular-nums" x-text="quantity" aria-live="polite"></span>
                                        <button type="button" class="grid size-10 place-items-center rounded-full text-zinc-600 hover:bg-zinc-50 disabled:opacity-40" @click="set(quantity + 1)" :disabled="quantity >= max" aria-label="Augmenter la quantité"><x-icon name="plus" class="size-4" /></button>
                                    </div>
                                    <span class="text-lg font-bold text-brand-900 tabular-nums" x-text="item.line_total_formatted"></span>
                                </div>
                            </div>
                        </li>
                    </template>
                </ul>
                <div class="flex items-center justify-between border-t border-zinc-100 bg-canvas px-6 py-4">
                    <a href="{{ route('catalog.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-900 hover:underline"><x-icon name="arrow-left" class="size-4" /> Continuer mes achats</a>
                </div>
            </div>

            <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
                <div class="card card-body">
                    <h2 class="text-lg font-bold">Récapitulatif</h2>
                    <dl class="mt-5 space-y-3 text-sm">
                        <div class="flex justify-between"><dt class="text-zinc-600">Sous-total (<span x-text="$store.cart.summary?.count"></span> articles)</dt><dd class="font-semibold tabular-nums" x-text="$store.cart.summary?.subtotal_formatted"></dd></div>
                        <div class="flex justify-between text-success-700" x-show="$store.cart.summary?.discount > 0">
                            <dt>Réduction (<span x-text="$store.cart.summary?.coupon"></span>)</dt>
                            <dd class="font-semibold tabular-nums" x-text="'−' + $store.cart.summary?.discount_formatted"></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-600">Livraison</dt>
                            <dd class="text-right text-zinc-600">
                                @if ($shippingQuotes->isNotEmpty())
                                    dès {{ $shippingQuotes->min('cost') ? money($shippingQuotes->min('cost')) : 'gratuit' }}
                                @else
                                    calculée au paiement
                                @endif
                            </dd>
                        </div>
                        <div class="divider"></div>
                        <div class="flex items-baseline justify-between"><dt class="font-semibold">Total estimé</dt><dd class="font-display text-2xl font-bold text-brand-900 tabular-nums" x-text="$store.cart.summary?.total_formatted"></dd></div>
                    </dl>

                    <div class="mt-5" x-data="couponForm()">
                        <template x-if="!$store.cart.summary?.coupon">
                            <form @submit.prevent="apply" class="flex gap-2">
                                <label for="coupon" class="sr-only">Code promo</label>
                                <input id="coupon" x-model="code" maxlength="40" placeholder="Code promo" class="input uppercase placeholder:normal-case">
                                <button type="submit" class="btn btn-secondary" :disabled="busy || !code.trim()">Appliquer</button>
                            </form>
                        </template>
                        <template x-if="$store.cart.summary?.coupon">
                            <div class="flex items-center justify-between rounded-xl bg-success-50 px-3.5 py-2.5 text-sm text-success-700 ring-1 ring-success-500/20">
                                <span class="flex items-center gap-2 font-semibold"><x-icon name="tag" class="size-4" /> <span x-text="$store.cart.summary.coupon"></span></span>
                                <button type="button" class="font-medium underline" @click="remove" :disabled="busy">Retirer</button>
                            </div>
                        </template>
                    </div>

                    <a href="{{ route('checkout.show') }}" class="btn btn-primary btn-lg mt-5 w-full">Passer la commande <x-icon name="arrow-right" class="size-4" /></a>
                    <p class="mt-3 flex items-center justify-center gap-1.5 text-xs text-zinc-500"><x-icon name="lock" class="size-3.5" /> Paiement sécurisé · Mobile Money ou à la livraison</p>
                </div>
                @if ($shippingQuotes->firstWhere('method.free_over_amount'))
                    @php($free = $shippingQuotes->firstWhere('method.free_over_amount'))
                    <div class="flex items-center gap-3 rounded-2xl bg-sand p-4 text-sm text-zinc-700">
                        <x-icon name="truck" class="text-brand-700" />
                        <p>{{ $free['method']->name }} offerte dès <strong>{{ money($free['method']->free_over_amount) }}</strong> d’achat.</p>
                    </div>
                @endif
            </aside>
        </div>
    </div>
@endsection
