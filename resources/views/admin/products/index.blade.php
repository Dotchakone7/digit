@extends('layouts.admin')

@section('title', 'Produits')

@section('content')
    <x-admin.page-header title="Produits" :subtitle="$products->total().' produit(s)'">
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nouveau produit</a>
    </x-admin.page-header>

    <form method="GET" class="card mb-4 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-[1fr_200px_160px_160px_160px_auto]">
        <div class="relative"><x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-400" /><input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nom ou référence…" class="input pl-9" aria-label="Rechercher"></div>
        <x-form.select name="category" :options="$categories" :value="$filters['category'] ?? ''" placeholder="Toutes catégories" aria-label="Catégorie" />
        <x-form.select name="status" :options="collect(\App\Enums\ProductStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])" :value="$filters['status'] ?? ''" placeholder="Tous statuts" aria-label="Statut" />
        <x-form.select name="stock" :options="['low' => 'Stock faible', 'out' => 'Épuisé']" :value="$filters['stock'] ?? ''" placeholder="Tout stock" aria-label="Stock" />
        <x-form.select name="sort" :options="['newest' => 'Plus récents', 'name' => 'Nom', 'price' => 'Prix', 'stock' => 'Stock croissant', 'sales' => 'Ventes']" :value="$filters['sort'] ?? 'newest'" aria-label="Tri" />
        <div class="flex gap-2"><button class="btn btn-secondary">Filtrer</button>@if (array_filter($filters))<a href="{{ route('admin.products.index') }}" class="btn btn-ghost">Effacer</a>@endif</div>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th>Produit</th><th>Catégorie</th><th class="text-right">Prix</th><th class="text-right">Stock</th><th class="text-right">Ventes</th><th>Statut</th><th class="text-right"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="size-11 shrink-0 overflow-hidden rounded-lg bg-zinc-100"><x-product-image :src="$product->image_url" alt="" /></span>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.products.edit', $product) }}" class="block max-w-xs truncate font-medium text-zinc-900 hover:underline">{{ $product->name }}</a>
                                        <p class="font-mono text-xs text-zinc-500">{{ $product->sku }}@if ($product->variants_count) · {{ $product->variants_count }} variantes @endif</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-zinc-600">{{ $product->category?->name ?? '—' }}</td>
                            <td class="text-right tabular-nums">
                                <span class="font-medium">{{ money($product->currentPrice()) }}</span>
                                @if ($product->isOnSale())<span class="block text-xs text-zinc-500 line-through">{{ money($product->price) }}</span>@endif
                            </td>
                            <td class="text-right">
                                <span @class(['font-semibold tabular-nums', 'text-danger-700' => $product->stock === 0, 'text-warning-700' => $product->isLowStock(), 'text-zinc-900' => ! $product->isLowStock() && $product->stock > 0])>{{ $product->stock }}</span>
                            </td>
                            <td class="text-right tabular-nums text-zinc-600">{{ $product->sales_count }}</td>
                            <td><span class="badge badge-{{ $product->status->tone() }}">{{ $product->status->label() }}</span></td>
                            <td>
                                <div class="flex items-center justify-end gap-1" x-data="{ open: false }" @click.outside="open = false">
                                    @if ($product->isPublished())<a href="{{ route('products.show', $product) }}" target="_blank" class="btn-icon size-8" aria-label="Voir sur la boutique"><x-icon name="eye" class="size-4" /></a>@endif
                                    <a href="{{ route('admin.products.edit', $product) }}" class="btn-icon size-8" aria-label="Modifier"><x-icon name="edit" class="size-4" /></a>
                                    <div class="relative">
                                        <button type="button" class="btn-icon size-8" @click="open = !open" aria-label="Plus d’actions" aria-haspopup="true"><x-icon name="chevron-down" class="size-4" /></button>
                                        <div x-show="open" x-cloak x-transition class="absolute right-0 z-20 mt-1 w-44 rounded-xl bg-white p-1.5 text-left shadow-lg ring-1 ring-zinc-200">
                                            @foreach (\App\Enums\ProductStatus::cases() as $status)
                                                @continue($status === $product->status)
                                                <form method="POST" action="{{ route('admin.products.status', $product) }}">@csrf<input type="hidden" name="status" value="{{ $status->value }}">
                                                    <button class="w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-zinc-50">{{ ['published' => 'Publier', 'draft' => 'Dépublier (brouillon)', 'archived' => 'Désactiver'][$status->value] }}</button>
                                                </form>
                                            @endforeach
                                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" data-confirm="Le produit sera retiré de la boutique. Les commandes passées gardent leur historique." data-confirm-title="Supprimer « {{ $product->name }} » ?" data-confirm-label="Supprimer">
                                                @csrf @method('DELETE')
                                                <button class="w-full rounded-lg px-3 py-2 text-left text-sm text-danger-700 hover:bg-danger-50">Supprimer</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty-state icon="tag" title="Aucun produit" text="Aucun produit ne correspond à ces critères."><a href="{{ route('admin.products.create') }}" class="btn btn-primary">Créer un produit</a></x-empty-state></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $products->links() }}</div>
@endsection
