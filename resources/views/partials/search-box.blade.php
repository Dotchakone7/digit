<form action="{{ route('catalog.index') }}" method="GET" role="search" class="relative w-full max-w-xl"
      x-data="searchBox(@js(request('q', '')))" @click.outside="open = false" @submit="submit($event)">
    <label for="{{ $id }}" class="sr-only">Rechercher un produit</label>
    <x-icon name="search" class="pointer-events-none absolute top-1/2 left-4 size-[18px] -translate-y-1/2 text-zinc-400" />
    <input id="{{ $id }}" type="search" name="q" x-model="q" @input.debounce.250ms="search" @focus="q.length >= 2 && (open = true)"
           @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.escape="open = false"
           placeholder="Rechercher un produit, une marque…" autocomplete="off" maxlength="100"
           class="w-full rounded-full border-0 bg-zinc-100 py-2.5 pr-4 pl-11 text-sm text-zinc-900 ring-1 ring-transparent transition placeholder:text-zinc-500 focus:bg-white focus:ring-2 focus:ring-brand-900 focus:outline-none"
           role="combobox" aria-autocomplete="list" :aria-expanded="open.toString()" aria-controls="{{ $id }}-results">

    <div x-show="open" x-cloak x-transition.opacity.duration.150ms id="{{ $id }}-results"
         class="absolute inset-x-0 top-full z-50 mt-2 overflow-hidden rounded-2xl bg-white shadow-[var(--shadow-lift)] ring-1 ring-zinc-900/5" role="listbox">
        <div x-show="loading" class="space-y-3 p-4">
            <div class="flex gap-3"><div class="skeleton size-11"></div><div class="flex-1 space-y-2"><div class="skeleton h-3.5 w-2/3"></div><div class="skeleton h-3 w-1/4"></div></div></div>
            <div class="flex gap-3"><div class="skeleton size-11"></div><div class="flex-1 space-y-2"><div class="skeleton h-3.5 w-1/2"></div><div class="skeleton h-3 w-1/5"></div></div></div>
        </div>
        <div x-show="!loading">
            <template x-if="results.categories.length">
                <div class="border-b border-zinc-100 p-2">
                    <p class="px-2 py-1 text-[11px] font-bold tracking-widest text-zinc-400 uppercase">Catégories</p>
                    <template x-for="(cat, i) in results.categories" :key="cat.url">
                        <a :href="cat.url" class="flex items-center gap-2 rounded-xl px-2 py-2 text-sm font-medium text-brand-900 hover:bg-zinc-50" :class="active === i && 'bg-zinc-50'" role="option">
                            <x-icon name="folder" class="size-4 text-zinc-400" /> <span x-text="cat.name"></span>
                        </a>
                    </template>
                </div>
            </template>
            <template x-if="results.products.length">
                <div class="p-2">
                    <template x-for="(product, i) in results.products" :key="product.url">
                        <a :href="product.url" class="flex items-center gap-3 rounded-xl p-2 hover:bg-zinc-50" :class="active === i + results.categories.length && 'bg-zinc-50'" role="option">
                            <span class="size-11 shrink-0 overflow-hidden rounded-lg bg-sand"><img x-show="product.image" :src="product.image" alt="" class="size-full object-cover"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-brand-900" x-text="product.name"></span>
                                <span class="text-xs font-semibold text-zinc-500" x-text="product.price"></span>
                            </span>
                        </a>
                    </template>
                    <button type="submit" class="mt-1 flex w-full items-center justify-center gap-1.5 rounded-xl px-2 py-2.5 text-sm font-semibold text-brand-900 hover:bg-zinc-50">
                        Voir tous les résultats <x-icon name="arrow-right" class="size-4" />
                    </button>
                </div>
            </template>
            <template x-if="!results.products.length && !results.categories.length">
                <div class="p-5 text-center text-sm text-zinc-500">
                    <p>Aucun résultat pour « <span class="font-medium text-zinc-700" x-text="q"></span> ».</p>
                    <p x-show="results.did_you_mean" class="mt-1">Vouliez-vous dire
                        <button type="button" class="link" @click="q = results.did_you_mean; search()" x-text="results.did_you_mean"></button> ?</p>
                </div>
            </template>
        </div>
    </div>
</form>
