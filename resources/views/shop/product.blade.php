@extends('layouts.shop')

@php
    $images = $product->images->map(fn ($i) => ['url' => $i->url, 'alt' => $i->alt ?: $product->name])->values();
    $variants = $product->activeVariants->map(fn ($v) => [
        'id' => $v->id, 'name' => $v->name, 'stock' => $v->stock,
        'price' => $v->price ? money($v->price) : null,
    ])->values();
    $crumbs = ['Boutique' => route('catalog.index')];
    if ($product->category?->parent) { $crumbs[$product->category->parent->name] = route('catalog.category', $product->category->parent); }
    if ($product->category) { $crumbs[$product->category->name] = route('catalog.category', $product->category); }
    $crumbs[$product->name] = null;
@endphp

@section('title', $product->meta_title ?: $product->name)
@section('meta_description', $product->meta_description ?: ($product->short_description ?: \Illuminate\Support\Str::limit(strip_tags($product->description), 160)))
@section('og_type', 'product')
@section('og_image', $product->image_url ?? '')
@section('canonical', route('products.show', $product))

@push('head')
<script type="application/ld+json">
{!! json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product->name,
    'sku' => $product->sku,
    'description' => $product->short_description ?: \Illuminate\Support\Str::limit(strip_tags($product->description), 300),
    'image' => $images->pluck('url')->all(),
    'category' => $product->category?->name,
    'brand' => ['@type' => 'Brand', 'name' => config('shop.name')],
    'offers' => [
        '@type' => 'Offer',
        'url' => route('products.show', $product),
        'priceCurrency' => config('shop.currency.code'),
        'price' => \App\Support\Money::toMajor($product->currentPrice()),
        'availability' => $product->isInStock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        'itemCondition' => 'https://schema.org/NewCondition',
    ],
    'aggregateRating' => $product->reviews_count > 0 ? [
        '@type' => 'AggregateRating', 'ratingValue' => $product->rating_avg, 'reviewCount' => $product->reviews_count,
    ] : null,
]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
</script>
@endpush

@section('content')
    <div class="container-shop pt-6 pb-4">
        <x-breadcrumbs :items="$crumbs" />
    </div>

    <div class="container-shop" x-data="productPurchase({
            productId: {{ $product->id }},
            stock: {{ $product->stock }},
            price: @js(money($product->currentPrice())),
            variants: @js($variants),
            maxPerLine: {{ (int) config('shop.catalog.max_quantity_per_line') }},
            checkoutUrl: @js(route('checkout.show')),
        })">
        <div class="grid gap-8 lg:grid-cols-2 lg:gap-14">
            {{-- Gallery --}}
            <div x-data="gallery(@js($images))" class="lg:sticky lg:top-24 lg:self-start">
                <div class="group relative aspect-square cursor-zoom-in overflow-hidden rounded-[var(--radius-card)] bg-white ring-1 ring-zinc-900/5"
                     @mouseenter="zoom = window.matchMedia('(hover: hover)').matches" @mouseleave="zoom = false" @mousemove="track($event)" @click="current && (lightbox = true)">
                    <template x-if="current">
                        <img :src="current.url" :alt="current.alt" class="size-full object-cover transition-transform duration-200 ease-out"
                             :style="zoom ? `transform: scale(2); transform-origin: ${origin}` : ''">
                    </template>
                    <template x-if="!current"><x-product-image :alt="$product->name" /></template>
                    <div class="absolute top-4 left-4 flex flex-col gap-1.5">
                        @if ($product->isOnSale())<span class="badge badge-accent text-sm">-{{ $product->discountPercent() }} %</span>@endif
                        @if ($product->isNew())<span class="badge bg-white text-brand-900 shadow-sm">Nouveau</span>@endif
                    </div>
                    <span class="pointer-events-none absolute right-4 bottom-4 hidden items-center gap-1.5 rounded-full bg-white/90 px-3 py-1.5 text-xs font-medium text-zinc-600 shadow-sm transition group-hover:opacity-0 sm:inline-flex">
                        <x-icon name="zoom" class="size-3.5" /> Survolez pour zoomer
                    </span>
                    <template x-if="images.length > 1">
                        <div class="absolute inset-x-3 top-1/2 flex -translate-y-1/2 justify-between sm:hidden">
                            <button type="button" class="grid size-9 place-items-center rounded-full bg-white/90 shadow" @click.stop="go(index - 1)" aria-label="Image précédente"><x-icon name="chevron-left" class="size-4" /></button>
                            <button type="button" class="grid size-9 place-items-center rounded-full bg-white/90 shadow" @click.stop="go(index + 1)" aria-label="Image suivante"><x-icon name="chevron-right" class="size-4" /></button>
                        </div>
                    </template>
                </div>
                <template x-if="images.length > 1">
                    <div class="scrollbar-none mt-3 flex gap-2 overflow-x-auto sm:mt-4 sm:gap-3">
                        <template x-for="(image, i) in images" :key="i">
                            <button type="button" @click="go(i)" class="size-16 shrink-0 overflow-hidden rounded-xl bg-white ring-2 transition sm:size-20"
                                    :class="i === index ? 'ring-brand-900' : 'ring-transparent opacity-70 hover:opacity-100'" :aria-label="'Afficher l’image ' + (i + 1)" :aria-current="i === index">
                                <img :src="image.url" alt="" class="size-full object-cover" loading="lazy">
                            </button>
                        </template>
                    </div>
                </template>

                {{-- Lightbox --}}
                <div x-show="lightbox" x-cloak x-transition.opacity class="fixed inset-0 z-[90] flex items-center justify-center bg-brand-950/90 p-4" role="dialog" aria-modal="true" aria-label="Image agrandie"
                     @keydown.escape.window="lightbox = false" @keydown.arrow-right.window="lightbox && go(index + 1)" @keydown.arrow-left.window="lightbox && go(index - 1)">
                    <button type="button" class="absolute top-4 right-4 grid size-11 place-items-center rounded-full bg-white/10 text-white hover:bg-white/20" @click="lightbox = false" aria-label="Fermer"><x-icon name="x" /></button>
                    <img x-show="current" :src="current?.url" :alt="current?.alt" class="max-h-[88vh] max-w-full rounded-2xl object-contain" x-trap="lightbox">
                </div>
            </div>

            {{-- Purchase panel --}}
            <div class="lg:py-2">
                @if ($product->category)
                    <a href="{{ route('catalog.category', $product->category) }}" class="text-xs font-bold tracking-[0.16em] text-accent-700 uppercase hover:underline">{{ $product->category->name }}</a>
                @endif
                <h1 class="mt-2 text-3xl leading-tight font-extrabold sm:text-4xl">{{ $product->name }}</h1>
                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-zinc-500">
                    @if ($product->reviews_count)
                        <a href="#avis" class="flex items-center gap-2 hover:text-brand-900"><x-rating :value="$product->rating_avg" /> {{ number_format($product->rating_avg, 1, ',', '') }} · {{ $product->reviews_count }} avis</a>
                    @endif
                    <span>Réf. <span class="font-mono text-zinc-700">{{ $product->sku }}</span></span>
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <span class="font-display text-3xl font-bold text-brand-900 tabular-nums sm:text-4xl" x-text="price">{{ money($product->currentPrice()) }}</span>
                    @if ($product->isOnSale())
                        <span class="text-lg text-zinc-400 line-through tabular-nums" x-show="!variant?.price"><span class="sr-only">Prix initial :</span>{{ money($product->price) }}</span>
                        <span class="badge badge-accent" x-show="!variant?.price">Économisez {{ money($product->price - $product->currentPrice()) }}</span>
                    @endif
                </div>
                @if ($product->isOnSale() && $product->sale_ends_at)
                    <p class="mt-2 flex items-center gap-1.5 text-sm font-medium text-accent-700"><x-icon name="clock" class="size-4" /> Offre valable jusqu’au {{ $product->sale_ends_at->translatedFormat('d F Y') }}</p>
                @endif

                @if ($product->short_description)
                    <p class="mt-5 leading-relaxed text-zinc-600">{{ $product->short_description }}</p>
                @endif

                {{-- Variants --}}
                @if ($variants->isNotEmpty())
                    <fieldset class="mt-7">
                        <legend class="mb-3 text-sm font-semibold text-brand-900">Option : <span class="font-normal text-zinc-600" x-text="variant?.name ?? 'choisissez'"></span></legend>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="v in variants" :key="v.id">
                                <button type="button" @click="variantId = v.id; clamp()" :disabled="v.stock <= 0"
                                        class="relative rounded-xl px-4 py-2.5 text-sm font-medium ring-1 transition disabled:cursor-not-allowed disabled:text-zinc-300 disabled:line-through"
                                        :class="variantId === v.id ? 'bg-brand-900 text-white ring-brand-900' : 'bg-white text-zinc-700 ring-zinc-200 hover:ring-brand-900'"
                                        :aria-pressed="(variantId === v.id).toString()" x-text="v.name"></button>
                            </template>
                        </div>
                    </fieldset>
                @endif

                {{-- Stock --}}
                <p class="mt-6 flex items-center gap-2 text-sm font-medium" aria-live="polite">
                    <template x-if="stock > {{ $product->lowStockThreshold() }}"><span class="flex items-center gap-2 text-success-700"><span class="size-2 rounded-full bg-success-500"></span> En stock, prêt à être expédié</span></template>
                    <template x-if="stock > 0 && stock <= {{ $product->lowStockThreshold() }}"><span class="flex items-center gap-2 text-warning-700"><span class="size-2 animate-pulse rounded-full bg-warning-500"></span> Plus que <span x-text="stock"></span> en stock — commandez vite</span></template>
                    <template x-if="stock <= 0"><span class="flex items-center gap-2 text-danger-700"><span class="size-2 rounded-full bg-danger-500"></span> Rupture de stock</span></template>
                </p>

                {{-- Quantity + actions --}}
                <div class="mt-5 flex flex-col gap-3 sm:flex-row" x-show="stock > 0">
                    <div class="inline-flex h-12 items-center justify-between rounded-full bg-white ring-1 ring-zinc-200 sm:w-36">
                        <button type="button" class="grid size-12 place-items-center rounded-full text-zinc-600 hover:bg-zinc-50 disabled:opacity-40" @click="quantity--; clamp()" :disabled="quantity <= 1" aria-label="Diminuer la quantité"><x-icon name="minus" class="size-4" /></button>
                        <label for="qty" class="sr-only">Quantité</label>
                        <input id="qty" type="number" min="1" :max="maxQuantity" x-model.number="quantity" @change="clamp()" class="w-12 border-0 bg-transparent text-center font-semibold tabular-nums focus:ring-0 focus:outline-none [&::-webkit-inner-spin-button]:appearance-none">
                        <button type="button" class="grid size-12 place-items-center rounded-full text-zinc-600 hover:bg-zinc-50 disabled:opacity-40" @click="quantity++; clamp()" :disabled="quantity >= maxQuantity" aria-label="Augmenter la quantité"><x-icon name="plus" class="size-4" /></button>
                    </div>
                    <button type="button" class="btn btn-primary btn-lg flex-1" @click="add()" :disabled="busy">
                        <span class="spinner" x-show="busy" x-cloak></span><x-icon name="bag" class="size-5" x-show="!busy" /> Ajouter au panier
                    </button>
                </div>
                <div class="mt-3 flex gap-3" x-show="stock > 0">
                    <button type="button" class="btn btn-accent btn-lg flex-1" @click="add(true)" :disabled="busy">Acheter maintenant</button>
                </div>

                <div class="mt-4 flex items-center gap-2">
                    <button type="button" x-data="wishlistButton('{{ route('wishlist.toggle', $product) }}', {{ app(\App\Services\WishlistService::class)->has($product) ? 'true' : 'false' }}, {{ auth()->check() ? 'true' : 'false' }})"
                            @click="toggle" :disabled="busy" class="btn btn-ghost" :aria-pressed="active.toString()">
                        <x-icon name="heart" class="size-5" x-bind:class="active && 'fill-accent-600 text-accent-600'" />
                        <span x-text="active ? 'Dans vos favoris' : 'Ajouter aux favoris'"></span>
                    </button>
                    <button type="button" x-data="share(@js(route('products.show', $product)), @js($product->name))" @click="share" class="btn btn-ghost">
                        <x-icon name="share" class="size-5" /> Partager
                    </button>
                </div>

                <ul class="mt-8 grid gap-3 rounded-2xl bg-sand/70 p-5 text-sm text-zinc-700 sm:grid-cols-2">
                    <li class="flex items-center gap-3"><x-icon name="truck" class="text-brand-700" /> Livraison rapide à domicile</li>
                    <li class="flex items-center gap-3"><x-icon name="shield" class="text-brand-700" /> Paiement 100 % sécurisé</li>
                    <li class="flex items-center gap-3"><x-icon name="refresh" class="text-brand-700" /> Retours sous {{ config('shop.orders.return_window_days') }} jours</li>
                    <li class="flex items-center gap-3"><x-icon name="headset" class="text-brand-700" /> Conseils avant achat</li>
                </ul>
            </div>
        </div>

        {{-- Sticky mobile purchase bar --}}
        <div class="fixed inset-x-0 bottom-0 z-40 border-t border-zinc-200 bg-white/95 p-3 backdrop-blur lg:hidden" x-show="stock > 0">
            <div class="flex items-center gap-3">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs text-zinc-500">{{ $product->name }}</p>
                    <p class="font-bold text-brand-900 tabular-nums" x-text="price"></p>
                </div>
                <button type="button" class="btn btn-primary" @click="add()" :disabled="busy"><x-icon name="bag" class="size-4" /> Ajouter</button>
            </div>
        </div>
    </div>

    {{-- Details --}}
    <div class="container-shop mt-16 grid gap-12 lg:grid-cols-3">
        <section class="lg:col-span-2" aria-labelledby="desc-title">
            <h2 id="desc-title" class="text-2xl font-bold">Description</h2>
            <div class="prose-shop mt-4">
                {!! nl2br(e($product->description)) !!}
            </div>
        </section>
        @if (! empty($product->specifications))
            <section aria-labelledby="specs-title">
                <h2 id="specs-title" class="text-2xl font-bold">Caractéristiques</h2>
                <dl class="mt-4 divide-y divide-zinc-100 overflow-hidden rounded-2xl bg-white ring-1 ring-zinc-900/5">
                    @foreach ($product->specifications as $spec)
                        <div class="flex justify-between gap-4 px-4 py-3 text-sm">
                            <dt class="text-zinc-500">{{ $spec['label'] ?? '' }}</dt>
                            <dd class="text-right font-medium text-zinc-800">{{ $spec['value'] ?? '' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endif
    </div>

    {{-- Reviews --}}
    <section id="avis" class="container-shop mt-16 scroll-mt-24" aria-labelledby="reviews-title">
        <div class="grid gap-10 lg:grid-cols-3">
            <div>
                <h2 id="reviews-title" class="text-2xl font-bold">Avis clients</h2>
                @if ($product->reviews_count)
                <div class="mt-5 card card-body">
                    <div class="flex items-center gap-4">
                        <span class="font-display text-5xl font-extrabold text-brand-900">{{ number_format($product->rating_avg, 1, ',', '') }}</span>
                        <div>
                            <x-rating :value="$product->rating_avg" size="size-5" />
                            <p class="mt-1 text-sm text-zinc-500">{{ $product->reviews_count }} avis vérifié{{ $product->reviews_count > 1 ? 's' : '' }}</p>
                        </div>
                    </div>
                    <div class="mt-5 space-y-2">
                        @for ($star = 5; $star >= 1; $star--)
                            @php($share = $product->reviews_count ? round(($ratingBreakdown[$star] ?? 0) * 100 / $product->reviews_count) : 0)
                            <div class="flex items-center gap-3 text-xs">
                                <span class="w-8 text-zinc-600">{{ $star }} ★</span>
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-zinc-100"><div class="h-full rounded-full bg-amber-400" style="width: {{ $share }}%"></div></div>
                                <span class="w-9 text-right text-zinc-500 tabular-nums">{{ $share }} %</span>
                            </div>
                        @endfor
                    </div>
                </div>
                @endif

                @if ($canReview)
                    <form method="POST" action="{{ route('reviews.store', $product) }}" class="mt-5 card card-body space-y-4" x-data="{ rating: {{ (int) old('rating', 5) }} }">
                        @csrf
                        <h3 class="font-sans text-base font-semibold">Donnez votre avis</h3>
                        <fieldset>
                            <legend class="label">Votre note</legend>
                            <div class="flex gap-1">
                                @for ($i = 1; $i <= 5; $i++)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="rating" value="{{ $i }}" class="peer sr-only" x-model.number="rating">
                                        <x-icon name="star" class="size-7 transition peer-focus-visible:ring-2" x-bind:class="rating >= {{ $i }} ? 'fill-amber-400 text-amber-400' : 'text-zinc-300'" />
                                        <span class="sr-only">{{ $i }} étoile{{ $i > 1 ? 's' : '' }}</span>
                                    </label>
                                @endfor
                            </div>
                            @error('rating')<p class="field-error">{{ $message }}</p>@enderror
                        </fieldset>
                        <x-form.input name="title" label="Titre (optionnel)" maxlength="120" />
                        <x-form.textarea name="comment" label="Votre commentaire" required minlength="10" maxlength="2000" rows="4" />
                        <button type="submit" class="btn btn-primary w-full" data-loading="Envoi…">Publier mon avis</button>
                        <p class="text-xs text-zinc-500">Votre avis sera publié après vérification par notre équipe.</p>
                    </form>
                @elseif ($hasReviewed)
                    <p class="mt-5 rounded-2xl bg-success-50 p-4 text-sm text-success-700">Merci, vous avez déjà donné votre avis sur ce produit.</p>
                @else
                    <p class="mt-5 rounded-2xl bg-sand/70 p-4 text-sm text-zinc-600">Seuls les clients ayant reçu ce produit peuvent laisser un avis.</p>
                @endif
            </div>

            <div class="lg:col-span-2">
                @forelse ($product->approvedReviews as $review)
                    <article class="border-b border-zinc-100 py-6 first:pt-0 lg:first:pt-12">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <span class="grid size-10 place-items-center rounded-full bg-sand text-sm font-bold text-brand-800">{{ mb_substr($review->authorName(), 0, 1) }}</span>
                                <div>
                                    <p class="text-sm font-semibold text-brand-900">{{ $review->authorName() }} <span class="ml-1 inline-flex items-center gap-1 text-xs font-medium text-success-700"><x-icon name="check-circle" class="size-3.5" /> Achat vérifié</span></p>
                                    <time class="text-xs text-zinc-500" datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->translatedFormat('d F Y') }}</time>
                                </div>
                            </div>
                            <x-rating :value="$review->rating" />
                        </div>
                        @if ($review->title)<h3 class="mt-4 font-sans text-base font-semibold">{{ $review->title }}</h3>@endif
                        <p class="mt-2 leading-relaxed text-zinc-600">{{ $review->comment }}</p>
                    </article>
                @empty
                    <div class="card lg:mt-12"><x-empty-state icon="message" title="Aucun avis pour le moment" text="Soyez le premier à partager votre expérience après votre achat." /></div>
                @endforelse
            </div>
        </div>
    </section>

    @if ($similar->isNotEmpty())
        <section class="container-shop mt-20 mb-8 lg:mb-0">
            <x-section-heading eyebrow="Vous aimerez aussi" title="Produits similaires" :link="$product->category ? route('catalog.category', $product->category) : null" />
            <div class="grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
                @foreach ($similar as $item)
                    <x-product-card :product="$item" />
                @endforeach
            </div>
        </section>
    @endif
@endsection
