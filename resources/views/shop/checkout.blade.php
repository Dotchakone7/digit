@extends('layouts.shop')

@section('title', 'Validation de la commande')
@section('robots', 'noindex, nofollow')

@php
    $defaultAddress = $addresses->first();
    $quotes = $shippingQuotes->map(fn ($q) => ['id' => $q['method']->id, 'cost' => $q['cost']])->values();
    $baseTotal = $summary->subtotal - $summary->discount;
@endphp

@section('content')
    <div class="container-shop py-8 lg:py-12"
         x-data="{
            shippingId: {{ (int) old('shipping_method_id', $shippingQuotes->first()['method']->id ?? 0) }},
            payment: @js(old('payment_method', $gateways->keys()->first())),
            addressId: @js(old('address_id', $defaultAddress?->id) ? (string) old('address_id', $defaultAddress?->id) : ''),
            quotes: @js($quotes),
            base: {{ $baseTotal }},
            format(v) { return new Intl.NumberFormat('fr-FR').format(v) + ' {{ config('shop.currency.symbol') }}' },
            get shipping() { return this.quotes.find(q => q.id === this.shippingId)?.cost ?? 0 },
            get total() { return this.base + this.shipping },
         }">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <a href="{{ route('cart.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-500 hover:text-brand-900"><x-icon name="arrow-left" class="size-4" /> Retour au panier</a>
                <h1 class="mt-2 text-3xl font-extrabold sm:text-4xl">Finaliser ma commande</h1>
            </div>
            <ol class="hidden items-center gap-2 text-xs font-semibold text-zinc-400 md:flex" aria-label="Étapes">
                @foreach (['Coordonnées', 'Adresse', 'Livraison', 'Paiement', 'Confirmation'] as $i => $step)
                    <li class="flex items-center gap-2">
                        <span @class(['grid size-6 place-items-center rounded-full', 'bg-brand-900 text-white' => $i < 4, 'bg-zinc-200 text-zinc-500' => $i === 4])>{{ $i + 1 }}</span>
                        <span @class(['text-brand-900' => $i < 4])>{{ $step }}</span>
                        @unless ($loop->last)<span class="h-px w-6 bg-zinc-200"></span>@endunless
                    </li>
                @endforeach
            </ol>
        </div>

        <form method="POST" action="{{ route('checkout.store') }}" class="mt-8 grid gap-8 lg:grid-cols-[1fr_400px]" novalidate>
            @csrf
            <div class="space-y-6">
                {{-- 1. Contact --}}
                <section class="card card-body" aria-labelledby="step-contact">
                    <h2 id="step-contact" class="flex items-center gap-3 text-lg font-bold"><span class="grid size-7 place-items-center rounded-full bg-brand-900 text-sm text-white">1</span> Vos coordonnées</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <x-form.input name="customer_name" label="Nom complet" :value="$user->name" required autocomplete="name" class="sm:col-span-2" />
                        <x-form.input name="customer_email" type="email" label="E-mail" :value="$user->email" required autocomplete="email" />
                        <x-form.input name="customer_phone" type="tel" label="Téléphone" :value="$user->phone" required autocomplete="tel" hint="Le livreur vous appellera sur ce numéro." />
                    </div>
                </section>

                {{-- 2. Address --}}
                <section class="card card-body" aria-labelledby="step-address">
                    <h2 id="step-address" class="flex items-center gap-3 text-lg font-bold"><span class="grid size-7 place-items-center rounded-full bg-brand-900 text-sm text-white">2</span> Adresse de livraison</h2>
                    @if ($addresses->isNotEmpty())
                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            @foreach ($addresses as $address)
                                <label class="relative flex cursor-pointer gap-3 rounded-2xl p-4 ring-1 transition" :class="addressId === '{{ $address->id }}' ? 'ring-2 ring-brand-900 bg-brand-50/40' : 'ring-zinc-200 hover:ring-zinc-300'">
                                    <input type="radio" name="address_id" value="{{ $address->id }}" x-model="addressId" class="mt-1 accent-brand-900">
                                    <span class="text-sm">
                                        <span class="flex items-center gap-2 font-semibold text-brand-900">{{ $address->label ?: 'Adresse' }} @if ($address->is_default)<span class="badge badge-neutral">Par défaut</span>@endif</span>
                                        <span class="mt-1 block text-zinc-600">{{ $address->full_name }} · {{ $address->phone }}</span>
                                        <span class="block text-zinc-500">{{ $address->oneLine() }}</span>
                                    </span>
                                </label>
                            @endforeach
                            <label class="flex cursor-pointer items-center gap-3 rounded-2xl p-4 ring-1 transition" :class="addressId === '' ? 'ring-2 ring-brand-900 bg-brand-50/40' : 'ring-zinc-200 hover:ring-zinc-300'">
                                <input type="radio" name="address_id" value="" x-model="addressId" class="accent-brand-900">
                                <span class="flex items-center gap-2 text-sm font-semibold text-brand-900"><x-icon name="plus" class="size-4" /> Nouvelle adresse</span>
                            </label>
                        </div>
                    @endif
                    <div x-show="addressId === ''" x-collapse @if ($addresses->isNotEmpty()) x-cloak @endif>
                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <x-form.input name="address[full_name]" label="Destinataire" :value="$user->name" autocomplete="name" />
                            <x-form.input name="address[phone]" type="tel" label="Téléphone du destinataire" :value="$user->phone" autocomplete="tel" />
                            <x-form.input name="address[city]" label="Ville" value="Abidjan" autocomplete="address-level2" />
                            <x-form.input name="address[district]" label="Commune / quartier" placeholder="Ex. : Cocody Angré" />
                            <x-form.input name="address[street]" label="Adresse" placeholder="Rue, lot, immeuble, porte…" autocomplete="street-address" class="sm:col-span-2" />
                            <x-form.input name="address[landmark]" label="Point de repère (optionnel)" placeholder="Ex. : en face de la pharmacie" class="sm:col-span-2" />
                            <x-form.checkbox name="save_address" label="Enregistrer cette adresse pour mes prochaines commandes" :checked="true" class="sm:col-span-2" />
                        </div>
                    </div>
                </section>

                {{-- 3. Shipping --}}
                <section class="card card-body" aria-labelledby="step-shipping">
                    <h2 id="step-shipping" class="flex items-center gap-3 text-lg font-bold"><span class="grid size-7 place-items-center rounded-full bg-brand-900 text-sm text-white">3</span> Mode de livraison</h2>
                    <div class="mt-5 grid gap-3">
                        @forelse ($shippingQuotes as ['method' => $method, 'cost' => $cost])
                            <label class="flex cursor-pointer items-center gap-4 rounded-2xl p-4 ring-1 transition" :class="shippingId === {{ $method->id }} ? 'ring-2 ring-brand-900 bg-brand-50/40' : 'ring-zinc-200 hover:ring-zinc-300'">
                                <input type="radio" name="shipping_method_id" value="{{ $method->id }}" x-model.number="shippingId" class="accent-brand-900">
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-sand text-brand-800"><x-icon :name="$method->price ? 'truck' : 'store'" class="size-[18px]" /></span>
                                <span class="flex-1 text-sm">
                                    <span class="block font-semibold text-brand-900">{{ $method->name }}</span>
                                    <span class="block text-zinc-500">{{ $method->description }}@if ($method->estimated_delay) · {{ $method->estimated_delay }}@endif</span>
                                </span>
                                <span class="text-sm font-bold text-brand-900">{{ $cost ? money($cost) : 'Gratuit' }}</span>
                            </label>
                        @empty
                            <p class="rounded-xl bg-warning-50 p-4 text-sm text-warning-700">Aucun mode de livraison n’est configuré. Contactez la boutique.</p>
                        @endforelse
                        @error('shipping_method_id')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </section>

                {{-- 4. Payment --}}
                <section class="card card-body" aria-labelledby="step-payment">
                    <h2 id="step-payment" class="flex items-center gap-3 text-lg font-bold"><span class="grid size-7 place-items-center rounded-full bg-brand-900 text-sm text-white">4</span> Paiement</h2>
                    <div class="mt-5 grid gap-3">
                        @foreach ($gateways as $code => $gateway)
                            <label class="flex cursor-pointer items-center gap-4 rounded-2xl p-4 ring-1 transition" :class="payment === '{{ $code }}' ? 'ring-2 ring-brand-900 bg-brand-50/40' : 'ring-zinc-200 hover:ring-zinc-300'">
                                <input type="radio" name="payment_method" value="{{ $code }}" x-model="payment" class="accent-brand-900">
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-sand text-brand-800"><x-icon :name="$code === 'cash_on_delivery' ? 'wallet' : ($code === 'manual_mobile_money' ? 'phone' : 'card')" class="size-[18px]" /></span>
                                <span class="flex-1 text-sm">
                                    <span class="block font-semibold text-brand-900">{{ $gateway->label() }}</span>
                                    <span class="block text-zinc-500">{{ $gateway->description() }}</span>
                                </span>
                            </label>
                        @endforeach
                        @error('payment_method')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <x-form.textarea name="notes" label="Instructions pour la livraison (optionnel)" rows="2" maxlength="500" class="mt-5" placeholder="Horaires de disponibilité, digicode…" />
                </section>
            </div>

            {{-- Order summary (sticky) --}}
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <div class="card card-body">
                    <h2 class="text-lg font-bold">Votre commande</h2>
                    <ul class="mt-4 max-h-72 space-y-4 overflow-y-auto pr-1">
                        @foreach ($summary->items as $item)
                            <li class="flex gap-3">
                                <span class="relative size-16 shrink-0 overflow-hidden rounded-xl bg-sand">
                                    <x-product-image :src="$item->product->image_url" :alt="$item->product->name" />
                                    <span class="absolute -top-1 -right-1 grid size-5 place-items-center rounded-full bg-brand-900 text-[11px] font-bold text-white">{{ $item->quantity }}</span>
                                </span>
                                <span class="min-w-0 flex-1 text-sm">
                                    <span class="line-clamp-2 font-medium text-brand-900">{{ $item->product->name }}</span>
                                    @if ($item->variant)<span class="text-xs text-zinc-500">{{ $item->variant->name }}</span>@endif
                                </span>
                                <span class="text-sm font-semibold tabular-nums">{{ money($item->lineTotal()) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <dl class="mt-5 space-y-2.5 border-t border-zinc-100 pt-5 text-sm">
                        <div class="flex justify-between"><dt class="text-zinc-600">Sous-total</dt><dd class="font-medium tabular-nums">{{ money($summary->subtotal) }}</dd></div>
                        @if ($summary->discount)
                            <div class="flex justify-between text-success-700"><dt>Réduction ({{ $summary->coupon->code }})</dt><dd class="font-medium tabular-nums">−{{ money($summary->discount) }}</dd></div>
                        @endif
                        <div class="flex justify-between"><dt class="text-zinc-600">Livraison</dt><dd class="font-medium tabular-nums" x-text="shipping === 0 ? 'Offerte' : format(shipping)"></dd></div>
                        <div class="flex items-baseline justify-between border-t border-zinc-100 pt-3"><dt class="font-semibold">Total</dt><dd class="font-display text-2xl font-bold text-brand-900 tabular-nums" x-text="format(total)">{{ money($baseTotal) }}</dd></div>
                    </dl>
                    <button type="submit" class="btn btn-primary btn-lg mt-6 w-full" data-loading="Validation en cours…" @disabled($shippingQuotes->isEmpty() || $gateways->isEmpty())>
                        <x-icon name="lock" class="size-4" /> Confirmer la commande
                    </button>
                    <p class="mt-3 text-center text-xs leading-relaxed text-zinc-500">En confirmant, vous acceptez nos <a href="{{ route('pages.show', 'conditions-generales') }}" class="link" target="_blank">conditions générales de vente</a>. Le montant final est recalculé et vérifié par nos serveurs.</p>
                </div>
            </aside>
        </form>
    </div>
@endsection
