@extends('layouts.shop')

@section('meta_description', setting('hero_subtitle', config('shop.tagline')))

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => config('shop.name'),
    'url' => route('home'),
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => route('catalog.index').'?q={search_term_string}',
        'query-input' => 'required name=search_term_string',
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
</script>
@endpush

@section('content')
    {{-- Hero ------------------------------------------------------------------ --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-sand via-sand/60 to-canvas">
        <div class="pointer-events-none absolute -top-40 -right-40 size-[520px] rounded-full bg-accent-200/40 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-48 -left-32 size-[420px] rounded-full bg-brand-200/40 blur-3xl" aria-hidden="true"></div>

        <div class="container-shop relative grid items-center gap-12 py-12 sm:py-16 lg:grid-cols-2 lg:py-24">
            <div class="animate-fade-up">
                <p class="inline-flex items-center gap-2 rounded-full bg-white/80 px-3.5 py-1.5 text-xs font-semibold text-brand-800 shadow-sm ring-1 ring-zinc-900/5">
                    <span class="size-1.5 rounded-full bg-accent-500"></span>
                    {{ setting('hero_eyebrow', 'Nouvelle collection disponible') }}
                </p>
                <h1 class="mt-6 text-4xl leading-[1.08] font-extrabold sm:text-5xl lg:text-6xl">
                    {{ setting('hero_title', 'Découvrez les produits qui vous correspondent.') }}
                </h1>
                <p class="mt-5 max-w-lg text-base leading-relaxed text-zinc-600 sm:text-lg">
                    {{ setting('hero_subtitle', 'Une sélection exigeante, des prix justes et une livraison rapide partout dans votre ville. Payez en toute sécurité par Mobile Money ou à la livraison.') }}
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ $heroProduct ? route('products.show', $heroProduct) : route('catalog.index') }}" class="btn btn-primary btn-lg">
                        Acheter maintenant <x-icon name="arrow-right" class="size-4" />
                    </a>
                    <a href="{{ route('catalog.index') }}" class="btn btn-secondary btn-lg">Découvrir le catalogue</a>
                </div>
                <dl class="mt-10 grid max-w-md grid-cols-3 gap-4 border-t border-zinc-900/10 pt-6">
                    <div><dt class="text-xs text-zinc-500">Livraison</dt><dd class="mt-1 font-display text-lg font-bold text-brand-900">24–72 h</dd></div>
                    <div><dt class="text-xs text-zinc-500">Paiement</dt><dd class="mt-1 font-display text-lg font-bold text-brand-900">Sécurisé</dd></div>
                    <div><dt class="text-xs text-zinc-500">Retours</dt><dd class="mt-1 font-display text-lg font-bold text-brand-900">{{ config('shop.orders.return_window_days') }} jours</dd></div>
                </dl>
            </div>

            <div class="relative mx-auto w-full max-w-lg lg:max-w-none">
                @php($heroImage = setting('hero_image') ? \App\Support\Media::url(setting('hero_image')) : $heroProduct?->image_url)
                <div class="aspect-[4/5] overflow-hidden rounded-[2rem] bg-white shadow-[var(--shadow-lift)] ring-1 ring-zinc-900/5 sm:aspect-square lg:aspect-[4/5]">
                    <x-product-image :src="$heroImage" :alt="$heroProduct?->name ?? config('shop.name')" loading="eager" fetchpriority="high" />
                </div>
                @if ($heroProduct)
                    <a href="{{ route('products.show', $heroProduct) }}"
                       class="absolute -bottom-5 left-4 flex max-w-[85%] items-center gap-3 rounded-2xl bg-white/95 p-3 pr-5 shadow-[var(--shadow-lift)] ring-1 ring-zinc-900/5 backdrop-blur transition hover:-translate-y-0.5 sm:left-auto sm:-right-4 lg:-left-8">
                        <span class="size-14 shrink-0 overflow-hidden rounded-xl bg-sand"><x-product-image :src="$heroProduct->image_url" alt="" /></span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-brand-900">{{ $heroProduct->name }}</span>
                            <x-price :product="$heroProduct" size="sm" />
                        </span>
                    </a>
                @endif
            </div>
        </div>
    </section>

    {{-- Benefits ------------------------------------------------------------------- --}}
    <section class="border-y border-zinc-100 bg-white" aria-label="Nos engagements">
        <div class="container-shop grid grid-cols-2 gap-6 py-8 lg:grid-cols-4">
            @foreach ([
                ['truck', 'Livraison rapide', 'Partout en ville, sous 24 à 72 h'],
                ['shield', 'Paiement sécurisé', 'Mobile Money ou à la livraison'],
                ['award', 'Produits de qualité', 'Sélectionnés et contrôlés'],
                ['headset', 'Service client', 'Une équipe à votre écoute'],
            ] as [$icon, $title, $text])
                <div class="flex items-start gap-3.5">
                    <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-sand text-brand-800"><x-icon :name="$icon" /></span>
                    <div>
                        <h2 class="font-sans text-sm font-semibold text-brand-900">{{ $title }}</h2>
                        <p class="mt-0.5 text-xs leading-relaxed text-zinc-500 sm:text-sm">{{ $text }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Categories ------------------------------------------------------------------ --}}
    @if ($categories->isNotEmpty())
        <section class="container-shop pt-20">
            <x-section-heading eyebrow="Explorer" title="Nos catégories" :link="route('catalog.index')" link-label="Toute la boutique" />
            <div class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3">
                @foreach ($categories as $category)
                    <a href="{{ route('catalog.category', $category) }}"
                       @class(['group relative overflow-hidden rounded-[var(--radius-card)] bg-sand', 'aspect-[4/5] md:aspect-[4/3]' => true])>
                        <x-product-image :src="$category->image_url" :alt="$category->name" class="transition duration-700 ease-out group-hover:scale-105" />
                        <div class="absolute inset-0 bg-gradient-to-t from-brand-950/75 via-brand-950/10 to-transparent"></div>
                        <div class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-2 p-4 sm:p-6">
                            <div>
                                <h3 class="text-lg font-bold text-white sm:text-2xl">{{ $category->name }}</h3>
                                <p class="mt-0.5 text-xs text-white/75 sm:text-sm">{{ $category->products_count }} produit{{ $category->products_count > 1 ? 's' : '' }}</p>
                            </div>
                            <span class="hidden size-10 shrink-0 place-items-center rounded-full bg-white text-brand-900 transition group-hover:translate-x-0.5 sm:grid">
                                <x-icon name="arrow-right" class="size-4" />
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Popular -------------------------------------------------------------------- --}}
    @if ($popular->isNotEmpty())
        <section class="container-shop pt-20">
            <x-section-heading eyebrow="Les favoris" title="Produits populaires" :link="route('catalog.index', ['sort' => 'popular'])" />
            <div class="grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
                @foreach ($popular as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Deals ---------------------------------------------------------------------- --}}
    @if ($deals->isNotEmpty())
        <section class="container-shop pt-20">
            <div class="overflow-hidden rounded-[2rem] bg-brand-900">
                <div class="grid gap-8 p-6 sm:p-10 lg:grid-cols-[1fr_2fr] lg:items-center lg:p-12">
                    <div class="text-white">
                        <p class="inline-flex items-center gap-2 rounded-full bg-accent-700 px-3 py-1 text-xs font-bold tracking-wide uppercase">
                            <x-icon name="percent" class="size-3.5" /> Offres du moment
                        </p>
                        <h2 class="mt-5 text-3xl font-extrabold text-white sm:text-4xl">Des prix doux, pour un temps limité.</h2>
                        <p class="mt-3 text-brand-200">Profitez de nos promotions avant qu’il ne soit trop tard. Quantités limitées.</p>
                        <a href="{{ route('catalog.index', ['on_sale' => 1]) }}" class="btn btn-lg mt-8 bg-white text-brand-900 hover:bg-brand-50">Voir toutes les promotions <x-icon name="arrow-right" class="size-4" /></a>
                    </div>
                    <div class="grid grid-cols-2 gap-3 sm:gap-4">
                        @foreach ($deals as $product)
                            <x-product-card :product="$product" />
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- New arrivals (horizontal scroll on mobile) ------------------------------------ --}}
    @if ($newest->isNotEmpty())
        <section class="pt-20">
            <div class="container-shop">
                <x-section-heading eyebrow="Fraîchement arrivés" title="Nouveautés" :link="route('catalog.index', ['sort' => 'newest'])" />
            </div>
            <div class="scrollbar-none container-shop flex snap-x snap-mandatory gap-3 overflow-x-auto pb-2 sm:gap-5 lg:grid lg:grid-cols-4 lg:overflow-visible">
                @foreach ($newest as $product)
                    <div class="w-[46%] shrink-0 snap-start sm:w-[31%] lg:w-auto">
                        <x-product-card :product="$product" />
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Testimonials ----------------------------------------------------------------- --}}
    @if ($testimonials->isNotEmpty())
        <section class="container-shop pt-20">
            <x-section-heading eyebrow="Ils nous font confiance" title="Ce que disent nos clients" />
            <div class="grid gap-5 md:grid-cols-3">
                @foreach ($testimonials as $review)
                    <figure class="card card-body flex flex-col">
                        <x-rating :value="$review->rating" />
                        <blockquote class="mt-4 flex-1 text-[15px] leading-relaxed text-zinc-700">
                            “{{ \Illuminate\Support\Str::limit($review->comment, 220) }}”
                        </blockquote>
                        <figcaption class="mt-6 flex items-center gap-3 border-t border-zinc-100 pt-4">
                            <span class="grid size-10 place-items-center rounded-full bg-sand text-sm font-bold text-brand-800">{{ mb_substr($review->authorName(), 0, 1) }}</span>
                            <span>
                                <span class="block text-sm font-semibold text-brand-900">{{ $review->authorName() }}</span>
                                <a href="{{ route('products.show', $review->product) }}" class="text-xs text-zinc-500 hover:text-brand-900">a acheté {{ $review->product->name }}</a>
                            </span>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Newsletter ----------------------------------------------------------------- --}}
    <section class="container-shop pt-20">
        <div class="relative overflow-hidden rounded-[2rem] bg-sand px-6 py-12 sm:px-12 lg:flex lg:items-center lg:justify-between lg:gap-12 lg:py-14">
            <div class="pointer-events-none absolute -top-20 -right-20 size-72 rounded-full bg-accent-200/50 blur-3xl" aria-hidden="true"></div>
            <div class="relative max-w-xl">
                <h2 class="text-2xl font-bold sm:text-3xl">Restez informé(e) de nos nouveautés</h2>
                <p class="mt-2 text-zinc-600">Offres exclusives, nouvelles collections et bons plans. Un e-mail de temps en temps, jamais de spam.</p>
            </div>
            <form method="POST" action="{{ route('newsletter.store') }}" class="relative mt-6 flex w-full max-w-md flex-col gap-2 sm:flex-row lg:mt-0">
                @csrf
                <label for="newsletter-email" class="sr-only">Adresse e-mail</label>
                <input id="newsletter-email" type="email" name="email" required maxlength="190" placeholder="votre@email.com" autocomplete="email"
                       class="input rounded-full bg-white px-5 py-3">
                <button type="submit" class="btn btn-primary px-6 py-3" data-loading="Envoi…">S’inscrire</button>
            </form>
        </div>
    </section>
@endsection
