<div>
    {{-- Filters: results update while typing --}}
    <div class="card mb-4 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-[1fr_200px_160px_160px]">
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-400" />
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Nom ou référence…" class="input pl-9" aria-label="Rechercher un produit">
            <span wire:loading.delay class="spinner absolute top-1/2 right-3 size-4 -translate-y-1/2 text-brand-700"></span>
        </div>
        <x-form.select name="category" wire:model.live="category" :options="$categories" placeholder="Toutes catégories" aria-label="Catégorie" />
        <x-form.select name="status" wire:model.live="status" :options="collect(\App\Enums\ProductStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])" placeholder="Tous statuts" aria-label="Statut" />
        <x-form.select name="stock" wire:model.live="stock" :options="['low' => 'Stock faible', 'out' => 'Épuisé']" placeholder="Tout stock" aria-label="Stock" />
    </div>

    {{-- Bulk actions bar --}}
    <div x-show="$wire.selected.length" x-cloak x-transition class="mb-4 flex flex-wrap items-center gap-2 rounded-xl bg-brand-900 px-4 py-3 text-sm text-white">
        <span class="font-semibold"><span x-text="$wire.selected.length"></span> sélectionné(s)</span>
        <button type="button" wire:click="bulkStatus('published')" class="btn btn-sm bg-white text-brand-900 hover:bg-brand-50">Publier</button>
        <button type="button" wire:click="bulkStatus('draft')" class="btn btn-sm bg-white/10 text-white hover:bg-white/20">Dépublier</button>
        <button type="button" wire:click="bulkStatus('archived')" class="btn btn-sm bg-white/10 text-white hover:bg-white/20">Désactiver</button>
        <button type="button" @click="$wire.selected = []" class="ml-auto text-xs text-brand-200 hover:text-white">Annuler la sélection</button>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="search,category,status,stock,sortBy,gotoPage,nextPage,previousPage">
            <table class="table-admin">
                <thead>
                    <tr>
                        <th class="w-10"><input type="checkbox" class="checkbox" aria-label="Tout sélectionner"
                            x-bind:checked="$wire.selected.length > 0 && $wire.selected.length >= {{ $products->count() }}"
                            @change="$wire.selected = $event.target.checked ? @js($products->pluck('id')) : []"></th>
                        <x-admin.th-sort field="name" :sort-field="$sortField" :sort-direction="$sortDirection">Produit</x-admin.th-sort>
                        <x-admin.th-sort field="price" :sort-field="$sortField" :sort-direction="$sortDirection" align="right">Prix</x-admin.th-sort>
                        <x-admin.th-sort field="stock" :sort-field="$sortField" :sort-direction="$sortDirection" align="right">Stock</x-admin.th-sort>
                        <x-admin.th-sort field="sales_count" :sort-field="$sortField" :sort-direction="$sortDirection" align="right">Ventes</x-admin.th-sort>
                        <th>Statut</th>
                        <th class="text-right"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr wire:key="product-{{ $product->id }}" class="{{ in_array($product->id, $selected) ? 'bg-brand-50/60' : '' }}">
                            <td><input type="checkbox" class="checkbox" value="{{ $product->id }}" wire:model.live="selected" aria-label="Sélectionner {{ $product->name }}"></td>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="size-11 shrink-0 overflow-hidden rounded-lg bg-zinc-100"><x-product-image :src="$product->image_url" alt="" /></span>
                                    <div class="min-w-0">
                                        <a wire:navigate href="{{ route('admin.products.edit', $product) }}" class="block max-w-xs truncate font-medium text-zinc-900 hover:underline">{{ $product->name }}</a>
                                        <p class="text-xs text-zinc-500"><span class="font-mono">{{ $product->sku }}</span> · {{ $product->category?->name ?? 'Sans catégorie' }}@if ($product->variants_count) · {{ $product->variants_count }} variantes @endif</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-right tabular-nums">
                                <span class="font-medium">{{ money($product->currentPrice()) }}</span>
                                @if ($product->isOnSale())<span class="block text-xs text-zinc-500 line-through">{{ money($product->price) }}</span>@endif
                            </td>
                            <td class="text-right">
                                @if ($product->variants_count)
                                    <a wire:navigate href="{{ route('admin.products.edit', $product) }}" @class(['font-semibold tabular-nums hover:underline', 'text-danger-700' => $product->stock === 0, 'text-warning-700' => $product->isLowStock(), 'text-zinc-900' => ! $product->isLowStock() && $product->stock > 0]) title="Stock par variante">{{ $product->stock }}</a>
                                @else
                                    {{-- Inline stock edit: saved when leaving the field or pressing Enter --}}
                                    <input type="number" min="0" value="{{ $product->stock }}" aria-label="Stock de {{ $product->name }}"
                                           wire:change="updateStock({{ $product->id }}, $event.target.value)" @keydown.enter="$el.blur()"
                                           @class(['w-20 rounded-lg border-0 bg-transparent px-2 py-1 text-right font-semibold tabular-nums ring-1 ring-transparent transition hover:ring-zinc-200 focus:bg-white focus:ring-2 focus:ring-brand-900 focus:outline-none',
                                               'text-danger-700' => $product->stock === 0, 'text-warning-700' => $product->isLowStock(), 'text-zinc-900' => ! $product->isLowStock() && $product->stock > 0])>
                                @endif
                            </td>
                            <td class="text-right tabular-nums text-zinc-600">{{ $product->sales_count }}</td>
                            <td>
                                {{-- One click toggles publication --}}
                                <button type="button" wire:click="setStatus({{ $product->id }}, '{{ $product->status === \App\Enums\ProductStatus::Published ? 'draft' : 'published' }}')"
                                        class="badge badge-{{ $product->status->tone() }} cursor-pointer transition hover:ring-2 hover:ring-zinc-300"
                                        title="{{ $product->status === \App\Enums\ProductStatus::Published ? 'Cliquer pour dépublier' : 'Cliquer pour publier' }}">
                                    <span wire:loading.remove wire:target="setStatus({{ $product->id }}, '{{ $product->status === \App\Enums\ProductStatus::Published ? 'draft' : 'published' }}')">{{ $product->status->label() }}</span>
                                    <span wire:loading wire:target="setStatus({{ $product->id }}, '{{ $product->status === \App\Enums\ProductStatus::Published ? 'draft' : 'published' }}')" class="spinner size-3"></span>
                                </button>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1" x-data="{ open: false }" @click.outside="open = false">
                                    @if ($product->isPublished())<a href="{{ route('products.show', $product) }}" target="_blank" class="btn-icon size-8" aria-label="Voir sur la boutique"><x-icon name="eye" class="size-4" /></a>@endif
                                    <a wire:navigate href="{{ route('admin.products.edit', $product) }}" class="btn-icon size-8" aria-label="Modifier"><x-icon name="edit" class="size-4" /></a>
                                    <div class="relative">
                                        <button type="button" class="btn-icon size-8" @click="open = !open" aria-label="Plus d’actions" aria-haspopup="true"><x-icon name="chevron-down" class="size-4" /></button>
                                        <div x-show="open" x-cloak x-transition class="absolute right-0 z-20 mt-1 w-44 rounded-xl bg-white p-1.5 text-left shadow-lg ring-1 ring-zinc-200">
                                            @foreach (\App\Enums\ProductStatus::cases() as $status)
                                                @continue($status === $product->status)
                                                <button type="button" wire:click="setStatus({{ $product->id }}, '{{ $status->value }}')" @click="open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-zinc-50">{{ ['published' => 'Publier', 'draft' => 'Dépublier (brouillon)', 'archived' => 'Désactiver'][$status->value] }}</button>
                                            @endforeach
                                            <button type="button" class="w-full rounded-lg px-3 py-2 text-left text-sm text-danger-700 hover:bg-danger-50"
                                                    @click="open = false; if (await $store.confirm.ask({ title: @js('Supprimer « '.$product->name.' » ?'), message: 'Le produit sera retiré de la boutique. Les commandes passées gardent leur historique.', confirmLabel: 'Supprimer' })) $wire.delete({{ $product->id }})">Supprimer</button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty-state icon="tag" title="Aucun produit" text="Aucun produit ne correspond à ces critères."><a wire:navigate href="{{ route('admin.products.create') }}" class="btn btn-primary">Créer un produit</a></x-empty-state></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $products->links() }}</div>
</div>
