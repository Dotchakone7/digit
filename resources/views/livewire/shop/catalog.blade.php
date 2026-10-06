@php
    $title = $category?->name ?? (filled($filters['q']) ? 'Résultats pour « '.$filters['q'].' »' : ($filters['on_sale'] ? 'Promotions' : 'Toute la boutique'));
    $crumbs = ['Boutique' => route('catalog.index')];
    if ($category?->parent) {
        $crumbs[$category->parent->name] = route('catalog.category', $category->parent);
    }
    if ($category) {
        $crumbs[$category->name] = null;
    }
    $chips = array_filter([
        'q' => filled($filters['q']) ? 'Recherche : '.$filters['q'] : null,
        'min_price' => $filters['min_price'] ? 'Min. '.money($filters['min_price']) : null,
        'max_price' => $filters['max_price'] ? 'Max. '.money($filters['max_price']) : null,
        'in_stock' => $filters['in_stock'] ? 'En stock' : null,
        'on_sale' => $filters['on_sale'] ? 'En promotion' : null,
    ]);
@endphp

<div x-data="{ filters: false }">
    <div class="border-b border-zinc-100 bg-white">
        <div class="container-shop py-8 sm:py-10">
            <x-breadcrumbs :items="$crumbs" />
            <div class="mt-4 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold sm:text-4xl">{{ $title }}</h1>
                    @if ($category?->description)
                        <p class="mt-2 max-w-2xl text-zinc-600">{{ $category->description }}</p>
                    @endif
                </div>
                <p class="flex items-center gap-2 text-sm text-zinc-500" aria-live="polite">
                    <span class="spinner size-3.5 text-brand-700" wire:loading.delay aria-hidden="true"></span>
                    <span>{{ $products->total() }} produit{{ $products->total() > 1 ? 's' : '' }}</span>
                </p>
            </div>
            @if ($category?->children->isNotEmpty())
                <div class="scrollbar-none -mx-4 mt-6 flex gap-2 overflow-x-auto px-4 sm:mx-0 sm:flex-wrap sm:px-0">
                    @foreach ($category->children as $child)
                        <a wire:navigate href="{{ route('catalog.category', $child) }}" class="shrink-0 rounded-full bg-zinc-100 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-brand-900 hover:text-white">{{ $child->name }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="container-shop py-8">
        <div class="grid grid-cols-[minmax(0,1fr)] gap-8 lg:grid-cols-[250px_minmax(0,1fr)]">
            {{-- Filters: sidebar on desktop, drawer on mobile — every change applies instantly --}}
            <div x-show="filters" x-cloak class="fixed inset-0 z-[70] bg-brand-950/40 lg:hidden" x-transition.opacity @click="filters = false"></div>
            <aside class="fixed inset-y-0 left-0 z-[75] w-[86%] max-w-sm -translate-x-full overflow-y-auto bg-white p-5 shadow-2xl transition-transform duration-300 lg:static lg:z-auto lg:w-auto lg:max-w-none lg:translate-x-0 lg:overflow-visible lg:bg-transparent lg:p-0 lg:shadow-none"
                   :class="filters && 'translate-x-0'" aria-label="Filtres" @keydown.escape.window="filters = false">
                <div class="mb-4 flex items-center justify-between lg:hidden">
                    <h2 class="text-lg font-bold">Filtres</h2>
                    <button type="button" class="btn-icon" @click="filters = false" aria-label="Fermer les filtres"><x-icon name="x" /></button>
                </div>

                <div class="space-y-7 lg:sticky lg:top-28">
                    <div>
                        <label for="catalog-q" class="mb-3 block font-sans text-sm font-semibold text-brand-900">Rechercher</label>
                        <div class="relative">
                            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-400" />
                            <input id="catalog-q" type="search" wire:model.live.debounce.400ms="q" maxlength="100" placeholder="Nom, matière, référence…" class="input pl-9">
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-3 font-sans text-sm font-semibold text-brand-900">Catégories</h3>
                        <ul class="space-y-0.5 text-sm">
                            <li><a wire:navigate href="{{ route('catalog.index') }}" @class(['block rounded-lg px-2.5 py-1.5 transition hover:bg-white', 'bg-white font-semibold text-brand-900 shadow-xs' => ! $category, 'text-zinc-600' => $category])>Toutes les catégories</a></li>
                            @foreach ($categories as $cat)
                                <li wire:key="cat-{{ $cat->id }}">
                                    <a wire:navigate href="{{ route('catalog.category', $cat) }}" @class(['block rounded-lg px-2.5 py-1.5 transition hover:bg-white', 'bg-white font-semibold text-brand-900 shadow-xs' => $category?->is($cat), 'text-zinc-600' => ! $category?->is($cat)])>{{ $cat->name }}</a>
                                    @if ($cat->children->isNotEmpty() && ($category?->is($cat) || $category?->parent_id === $cat->id))
                                        <ul class="mt-0.5 ml-3 space-y-0.5 border-l border-zinc-200 pl-2">
                                            @foreach ($cat->children as $child)
                                                <li wire:key="cat-{{ $child->id }}"><a wire:navigate href="{{ route('catalog.category', $child) }}" @class(['block rounded-lg px-2.5 py-1 transition hover:bg-white', 'font-semibold text-brand-900' => $category?->is($child), 'text-zinc-500' => ! $category?->is($child)])>{{ $child->name }}</a></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <fieldset>
                        <legend class="mb-3 font-sans text-sm font-semibold text-brand-900">Prix ({{ config('shop.currency.symbol') }})</legend>
                        <div class="flex items-center gap-2">
                            <label class="sr-only" for="min_price">Prix minimum</label>
                            <input id="min_price" type="number" min="0" step="1" inputmode="numeric" placeholder="Min" wire:model.live.debounce.700ms="minPrice" class="input">
                            <span class="text-zinc-400">–</span>
                            <label class="sr-only" for="max_price">Prix maximum</label>
                            <input id="max_price" type="number" min="0" step="1" inputmode="numeric" placeholder="Max" wire:model.live.debounce.700ms="maxPrice" class="input">
                        </div>
                    </fieldset>

                    <fieldset class="space-y-3">
                        <legend class="mb-3 font-sans text-sm font-semibold text-brand-900">Disponibilité & offres</legend>
                        <label class="flex cursor-pointer items-center gap-3 text-sm text-zinc-700"><input type="checkbox" wire:model.live="inStock" class="checkbox"> En stock uniquement</label>
                        <label class="flex cursor-pointer items-center gap-3 text-sm text-zinc-700"><input type="checkbox" wire:model.live="onSale" class="checkbox"> En promotion</label>
                    </fieldset>

                    @if ($activeFilterCount || filled($filters['q']))
                        <button type="button" wire:click="resetFilters" class="btn btn-secondary w-full">Réinitialiser les filtres</button>
                    @endif
                    <button type="button" class="btn btn-primary w-full lg:hidden" @click="filters = false">Voir les {{ $products->total() }} produits</button>
                </div>
            </aside>

            <div class="min-w-0">
                {{-- Toolbar --}}
                <div class="mb-6 flex items-center justify-between gap-3">
                    <button type="button" class="btn btn-secondary lg:hidden" @click="filters = true">
                        <x-icon name="sliders" class="size-4" /> Filtres
                        @if ($activeFilterCount)<span class="grid size-5 place-items-center rounded-full bg-brand-900 text-[11px] text-white">{{ $activeFilterCount }}</span>@endif
                    </button>
                    <div class="ml-auto flex items-center gap-2">
                        <label for="sort" class="hidden text-sm text-zinc-500 sm:block">Trier par</label>
                        <div class="relative">
                            <select id="sort" wire:model.live="sort" class="input appearance-none rounded-full py-2 pr-9 font-medium">
                                @foreach ($sorts as $value => $label)
                                    <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-icon name="chevron-down" class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-zinc-400" />
                        </div>
                    </div>
                    <div class="hidden items-center rounded-full bg-white p-1 ring-1 ring-zinc-200 sm:flex" role="group" aria-label="Mode d’affichage">
                        <button type="button" wire:click="$set('view', 'grid')" @class(['grid size-8 place-items-center rounded-full transition', 'bg-brand-900 text-white' => $view === 'grid', 'text-zinc-500 hover:text-brand-900' => $view !== 'grid']) aria-label="Affichage en grille" aria-pressed="{{ $view === 'grid' ? 'true' : 'false' }}"><x-icon name="grid" class="size-4" /></button>
                        <button type="button" wire:click="$set('view', 'list')" @class(['grid size-8 place-items-center rounded-full transition', 'bg-brand-900 text-white' => $view === 'list', 'text-zinc-500 hover:text-brand-900' => $view !== 'list']) aria-label="Affichage en liste" aria-pressed="{{ $view === 'list' ? 'true' : 'false' }}"><x-icon name="list" class="size-4" /></button>
                    </div>
                </div>

                {{-- Active filter chips (one click removes a filter) --}}
                @if ($chips)
                    <div class="mb-6 flex flex-wrap gap-2">
                        @foreach ($chips as $key => $label)
                            <button type="button" wire:key="chip-{{ $key }}" wire:click="clear('{{ $key }}')" class="inline-flex animate-fade-up items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-800 ring-1 ring-brand-100 transition hover:bg-brand-100" aria-label="Retirer le filtre {{ $label }}">
                                {{ $label }} <x-icon name="x" class="size-3.5" />
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Skeletons while results load --}}
                <div wire:loading.delay.long class="w-full">
                    <div @class(['grid gap-3 sm:gap-5', 'grid-cols-2 xl:grid-cols-3' => $view === 'grid', 'grid-cols-1' => $view === 'list'])>
                        @for ($i = 0; $i < 6; $i++)
                            <x-product-card-skeleton />
                        @endfor
                    </div>
                </div>

                <div wire:loading.remove.delay.long>
                    @if ($products->isEmpty())
                        <div class="card">
                            <x-empty-state icon="search" title="Aucun produit trouvé" text="Essayez d’élargir votre recherche ou de retirer certains filtres.">
                                <div class="flex flex-col items-center gap-3">
                                    @if ($didYouMean)
                                        <p class="text-sm text-zinc-600">Vouliez-vous dire <button type="button" wire:click="search(@js($didYouMean))" class="link">{{ $didYouMean }}</button> ?</p>
                                    @endif
                                    <button type="button" wire:click="resetFilters" class="btn btn-primary">Réinitialiser les filtres</button>
                                </div>
                            </x-empty-state>
                        </div>
                    @else
                        <div @class(['grid gap-3 sm:gap-5', 'grid-cols-2 xl:grid-cols-3' => $view === 'grid', 'grid-cols-1' => $view === 'list'])>
                            @foreach ($products as $product)
                                <div wire:key="product-{{ $product->id }}-{{ $view }}" class="animate-fade-up" style="animation-delay: {{ min($loop->index, 8) * 35 }}ms">
                                    <x-product-card :product="$product" :layout="$view" />
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-10">{{ $products->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
