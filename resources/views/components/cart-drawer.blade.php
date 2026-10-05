{{-- Mini-cart: opens from the header without reloading the page. --}}
<div x-data x-show="$store.cart.open" x-cloak class="fixed inset-0 z-[80]" role="dialog" aria-modal="true" aria-labelledby="cart-drawer-title"
     @keydown.escape.window="$store.cart.open = false">
    <div x-show="$store.cart.open" x-transition.opacity.duration.300ms class="absolute inset-0 bg-brand-950/40 backdrop-blur-[2px]" @click="$store.cart.open = false"></div>

    <aside x-show="$store.cart.open" x-trap.noscroll="$store.cart.open"
           x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
           class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-white shadow-2xl">
        <header class="flex items-center justify-between border-b border-zinc-100 px-5 py-4">
            <h2 id="cart-drawer-title" class="text-lg font-bold">Mon panier <span class="text-zinc-500" x-show="$store.cart.count" x-text="'(' + $store.cart.count + ')'"></span></h2>
            <button type="button" class="btn-icon" @click="$store.cart.open = false" aria-label="Fermer le panier"><x-icon name="x" /></button>
        </header>

        <div class="flex-1 overflow-y-auto px-5 py-4">
            <template x-if="$store.cart.loading && !$store.cart.summary">
                <div class="space-y-4" aria-label="Chargement">
                    @for ($i = 0; $i < 3; $i++)
                        <div class="flex gap-4"><div class="skeleton size-20 shrink-0"></div><div class="flex-1 space-y-2 py-1"><div class="skeleton h-4 w-3/4"></div><div class="skeleton h-3 w-1/3"></div><div class="skeleton h-4 w-1/4"></div></div></div>
                    @endfor
                </div>
            </template>

            <template x-if="$store.cart.summary && $store.cart.summary.items.length === 0">
                <x-empty-state icon="bag" title="Votre panier est vide" text="Découvrez nos produits et laissez-vous tenter.">
                    <a href="{{ route('catalog.index') }}" class="btn btn-primary">Découvrir le catalogue</a>
                </x-empty-state>
            </template>

            <ul class="divide-y divide-zinc-100" x-show="$store.cart.summary?.items.length">
                <template x-for="item in $store.cart.summary?.items ?? []" :key="item.id + '-' + item.quantity">
                    <li class="flex gap-4 py-4" x-data="cartLine(item.id, item.quantity, item.max)" :class="busy && 'opacity-60'">
                        <a :href="item.url" class="size-20 shrink-0 overflow-hidden rounded-xl bg-sand">
                            <img x-show="item.image" :src="item.image" :alt="item.name" class="size-full object-cover">
                        </a>
                        <div class="flex min-w-0 flex-1 flex-col">
                            <a :href="item.url" class="line-clamp-2 text-sm font-semibold text-brand-900" x-text="item.name"></a>
                            <p class="text-xs text-zinc-500" x-show="item.variant" x-text="item.variant"></p>
                            <p class="text-xs font-medium text-danger-700" x-show="!item.available">Quantité indisponible — ajustez-la</p>
                            <div class="mt-auto flex items-center justify-between pt-2">
                                <div class="inline-flex items-center rounded-full ring-1 ring-zinc-200">
                                    <button type="button" class="grid size-8 place-items-center rounded-full text-zinc-600 hover:bg-zinc-100" @click="set(quantity - 1)" :disabled="busy" aria-label="Diminuer la quantité"><x-icon name="minus" class="size-3.5" /></button>
                                    <span class="w-7 text-center text-sm font-semibold tabular-nums" x-text="quantity"></span>
                                    <button type="button" class="grid size-8 place-items-center rounded-full text-zinc-600 hover:bg-zinc-100 disabled:opacity-40" @click="set(quantity + 1)" :disabled="busy || quantity >= max" aria-label="Augmenter la quantité"><x-icon name="plus" class="size-3.5" /></button>
                                </div>
                                <span class="text-sm font-semibold tabular-nums" x-text="item.line_total_formatted"></span>
                            </div>
                        </div>
                        <button type="button" class="self-start text-zinc-400 transition hover:text-danger-600" @click="remove()" aria-label="Retirer l’article"><x-icon name="trash" class="size-4" /></button>
                    </li>
                </template>
            </ul>
        </div>

        <footer class="border-t border-zinc-100 bg-canvas px-5 py-5" x-show="$store.cart.summary?.items.length">
            <dl class="space-y-1.5 text-sm">
                <div class="flex justify-between"><dt class="text-zinc-500">Sous-total</dt><dd class="font-medium tabular-nums" x-text="$store.cart.summary?.subtotal_formatted"></dd></div>
                <div class="flex justify-between text-success-700" x-show="$store.cart.summary?.discount > 0"><dt>Réduction <span x-text="'(' + $store.cart.summary?.coupon + ')'"></span></dt><dd class="font-medium tabular-nums" x-text="'−' + $store.cart.summary?.discount_formatted"></dd></div>
                <div class="flex justify-between text-zinc-500"><dt>Livraison</dt><dd>Calculée à l’étape suivante</dd></div>
            </dl>
            <div class="mt-4 grid gap-2">
                <a href="{{ route('checkout.show') }}" class="btn btn-primary btn-lg w-full">Passer la commande <x-icon name="arrow-right" class="size-4" /></a>
                <a href="{{ route('cart.index') }}" class="btn btn-ghost w-full">Voir le panier</a>
            </div>
        </footer>
    </aside>
</div>
