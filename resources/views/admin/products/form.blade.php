@extends('layouts.admin')

@section('title', $product->exists ? $product->name : 'Nouveau produit')

@php
    $specs = old('specifications', $product->specifications ?? []);
    $variants = old('variants', $product->exists ? $product->variants->map(fn ($v) => [
        'id' => $v->id, 'name' => $v->name, 'sku' => $v->sku, 'price' => \App\Support\Money::toMajor($v->price), 'stock' => $v->stock, 'is_active' => $v->is_active,
    ])->all() : []);
    $date = fn ($d) => $d ? $d->format('Y-m-d\TH:i') : null;
@endphp

@section('content')
    <x-admin.page-header :title="$product->exists ? 'Modifier le produit' : 'Nouveau produit'" :subtitle="$product->exists ? $product->name : null" :back="route('admin.products.index')">
        @if ($product->exists && $product->isPublished())
            <a href="{{ route('products.show', $product) }}" target="_blank" class="btn btn-secondary"><x-icon name="eye" class="size-4" /> Voir sur la boutique</a>
        @endif
    </x-admin.page-header>

    <form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
        @csrf
        @if ($product->exists) @method('PUT') @endif

        <div class="space-y-6">
            <section class="card space-y-4 p-5" x-data="slugger(@js(old('name', $product->name)), @js(old('slug', $product->slug)), {{ $product->exists ? 'true' : 'false' }})">
                <h2 class="text-base font-semibold">Informations</h2>
                <div>
                    <label for="name" class="label">Nom du produit <span class="text-danger-600">*</span></label>
                    <input id="name" name="name" x-model="name" @input="sync()" required maxlength="180" class="input @error('name') input-error @enderror">
                    @error('name')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="slug" class="label">Slug (URL)</label>
                        <div class="flex items-center rounded-lg ring-1 ring-zinc-200 focus-within:ring-2 focus-within:ring-brand-900"><span class="pl-3 text-xs text-zinc-500">/produit/</span><input id="slug" name="slug" x-model="slug" @input="locked = true" class="w-full border-0 bg-transparent px-1 py-2 text-sm focus:ring-0 focus:outline-none"></div>
                        @error('slug')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <x-form.input name="sku" label="Référence (SKU)" :value="$product->sku" required maxlength="64" class="[&_input]:font-mono [&_input]:uppercase" />
                </div>
                <x-form.textarea name="short_description" label="Résumé" :value="$product->short_description" rows="2" maxlength="500" hint="Affiché près du prix et dans les résultats de recherche." />
                <x-form.textarea name="description" label="Description détaillée" :value="$product->description" rows="8" maxlength="20000" />
            </section>

            <section class="card space-y-4 p-5">
                <h2 class="text-base font-semibold">Prix & stock</h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-form.input name="price" type="number" min="0" step="1" :label="'Prix ('.config('shop.currency.symbol').')'" :value="\App\Support\Money::toMajor($product->price)" required />
                    <x-form.input name="sale_price" type="number" min="0" step="1" label="Prix promotionnel" :value="\App\Support\Money::toMajor($product->sale_price)" hint="Laisser vide si aucune promotion." />
                    <x-form.input name="stock" type="number" min="0" label="Stock" :value="$product->stock" required hint="Calculé automatiquement si le produit a des variantes." />
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-form.input name="sale_starts_at" type="datetime-local" label="Début de la promotion" :value="$date($product->sale_starts_at)" />
                    <x-form.input name="sale_ends_at" type="datetime-local" label="Fin de la promotion" :value="$date($product->sale_ends_at)" />
                    <x-form.input name="low_stock_threshold" type="number" min="0" label="Seuil stock faible" :value="$product->low_stock_threshold" :hint="'Par défaut : '.config('shop.catalog.low_stock_threshold')" />
                </div>
            </section>

            <section class="card p-5" x-data="repeater(@js(array_values($variants)), { name: '', sku: '', price: '', stock: 0, is_active: true })">
                <div class="flex items-center justify-between">
                    <div><h2 class="text-base font-semibold">Variantes</h2><p class="text-sm text-zinc-500">Tailles, pointures, couleurs… chacune avec son stock.</p></div>
                    <button type="button" class="btn btn-secondary btn-sm" @click="add()"><x-icon name="plus" class="size-4" /> Ajouter</button>
                </div>
                <template x-if="rows.length">
                    <div class="mt-4 overflow-x-auto">
                        <table class="table-admin">
                            <thead><tr><th>Nom</th><th>Référence</th><th>Prix spécifique</th><th>Stock</th><th>Active</th><th></th></tr></thead>
                            <tbody>
                                <template x-for="(row, i) in rows" :key="row.id ?? row._key ?? i">
                                    <tr>
                                        <td><input type="hidden" :name="`variants[${i}][id]`" :value="row.id ?? ''"><input :name="`variants[${i}][name]`" x-model="row.name" class="input min-w-32" placeholder="Taille M" required aria-label="Nom de la variante"></td>
                                        <td><input :name="`variants[${i}][sku]`" x-model="row.sku" class="input min-w-32 font-mono uppercase" required aria-label="Référence"></td>
                                        <td><input type="number" min="0" :name="`variants[${i}][price]`" x-model="row.price" class="input w-32" placeholder="Prix produit" aria-label="Prix"></td>
                                        <td><input type="number" min="0" :name="`variants[${i}][stock]`" x-model="row.stock" class="input w-24" required aria-label="Stock"></td>
                                        <td><input type="hidden" :name="`variants[${i}][is_active]`" value="0"><input type="checkbox" :name="`variants[${i}][is_active]`" value="1" x-model="row.is_active" class="checkbox" aria-label="Active"></td>
                                        <td><button type="button" class="btn-icon size-8 text-zinc-400 hover:text-danger-600" @click="remove(i)" aria-label="Retirer la variante"><x-icon name="trash" class="size-4" /></button></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
                @error('variants.*')<p class="field-error">{{ $message }}</p>@enderror
                @foreach ($errors->get('variants.*') as $messages)@foreach ($messages as $m)<p class="field-error">{{ $m }}</p>@endforeach @endforeach
            </section>

            <section class="card p-5" x-data="repeater(@js(array_values($specs)), { label: '', value: '' })">
                <div class="flex items-center justify-between">
                    <div><h2 class="text-base font-semibold">Caractéristiques</h2><p class="text-sm text-zinc-500">Affichées dans le tableau de la fiche produit.</p></div>
                    <button type="button" class="btn btn-secondary btn-sm" @click="add()"><x-icon name="plus" class="size-4" /> Ajouter</button>
                </div>
                <div class="mt-4 space-y-2">
                    <template x-for="(row, i) in rows" :key="row._key ?? i">
                        <div class="flex gap-2">
                            <input :name="`specifications[${i}][label]`" x-model="row.label" class="input" placeholder="Matière" aria-label="Caractéristique">
                            <input :name="`specifications[${i}][value]`" x-model="row.value" class="input" placeholder="100 % coton" aria-label="Valeur">
                            <button type="button" class="btn-icon shrink-0 text-zinc-400 hover:text-danger-600" @click="remove(i)" aria-label="Retirer"><x-icon name="trash" class="size-4" /></button>
                        </div>
                    </template>
                </div>
            </section>

            <section class="card space-y-4 p-5">
                <h2 class="text-base font-semibold">Référencement (SEO)</h2>
                <x-form.input name="meta_title" label="Titre de la page" :value="$product->meta_title" maxlength="255" hint="Par défaut : nom du produit." />
                <x-form.textarea name="meta_description" label="Meta description" :value="$product->meta_description" rows="2" maxlength="320" hint="160 caractères recommandés. Par défaut : le résumé." />
            </section>
        </div>

        <div class="space-y-6">
            <section class="card space-y-4 p-5">
                <h2 class="text-base font-semibold">Publication</h2>
                <x-form.select name="status" label="Statut" :options="collect(\App\Enums\ProductStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])" :value="$product->status" />
                <x-form.select name="category_id" label="Catégorie" :options="$categories" :value="$product->category_id" placeholder="Sans catégorie" />
                <x-form.checkbox name="is_featured" label="Produit mis en avant" hint="Prioritaire sur la page d’accueil." :checked="$product->is_featured" />
                <button type="submit" class="btn btn-primary w-full" data-loading="Enregistrement…">{{ $product->exists ? 'Enregistrer les modifications' : 'Créer le produit' }}</button>
            </section>

            <section class="card p-5">
                <h2 class="text-base font-semibold">Images</h2>
                @if ($product->exists && $product->images->isNotEmpty())
                    <div class="mt-4 grid grid-cols-3 gap-2">
                        @foreach ($product->images as $image)
                            <div class="group relative aspect-square overflow-hidden rounded-lg bg-zinc-100 ring-2 {{ $image->is_primary ? 'ring-brand-900' : 'ring-transparent' }}">
                                <img src="{{ $image->url }}" alt="{{ $image->alt }}" class="size-full object-cover">
                                @if ($image->is_primary)<span class="absolute bottom-1 left-1 rounded bg-brand-900 px-1.5 text-[10px] font-semibold text-white">Principale</span>@endif
                                <div class="absolute inset-0 flex items-center justify-center gap-1 bg-zinc-900/50 opacity-0 transition group-focus-within:opacity-100 group-hover:opacity-100">
                                    @unless ($image->is_primary)
                                        <button type="submit" form="primary-{{ $image->id }}" class="grid size-8 place-items-center rounded-full bg-white text-zinc-900" aria-label="Définir comme principale"><x-icon name="star" class="size-4" /></button>
                                    @endunless
                                    <button type="submit" form="delete-image-{{ $image->id }}" class="grid size-8 place-items-center rounded-full bg-white text-danger-600" aria-label="Supprimer l’image"><x-icon name="trash" class="size-4" /></button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                {{-- Instant preview of the selected files (nothing is uploaded until the form is saved). --}}
                <div x-data="imagePicker({{ (int) config('shop.uploads.max_kb') * 1024 }})">
                    <label class="mt-4 flex cursor-pointer flex-col items-center gap-2 rounded-xl border-2 border-dashed p-6 text-center transition hover:border-brand-400 hover:bg-zinc-50"
                           x-bind:class="dragging ? 'border-brand-500 bg-brand-50' : 'border-zinc-200'"
                           x-on:dragover.prevent="dragging = true" x-on:dragleave.prevent="dragging = false" x-on:drop.prevent="drop($event)">
                        <x-icon name="upload" class="size-6 text-zinc-400" />
                        <span class="text-sm font-medium text-zinc-700">Ajouter des images <span class="font-normal text-zinc-500">ou glissez-les ici</span></span>
                        <span class="text-xs text-zinc-500">JPG, PNG ou WebP · {{ (int) (config('shop.uploads.max_kb') / 1024) }} Mo max · optimisées automatiquement</span>
                        <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="sr-only" x-ref="input" x-on:change="sync()">
                    </label>
                    <div x-show="previews.length" x-cloak class="mt-3 grid grid-cols-3 gap-2">
                        <template x-for="(preview, index) in previews" :key="preview.url">
                            <div class="animate-fade-up group relative aspect-square overflow-hidden rounded-lg bg-zinc-100 ring-2" x-bind:class="preview.tooBig ? 'ring-danger-500' : 'ring-accent-300'">
                                <img x-bind:src="preview.url" x-bind:alt="preview.name" class="size-full object-cover">
                                <span class="absolute bottom-1 left-1 rounded px-1.5 text-[10px] font-semibold text-white" x-bind:class="preview.tooBig ? 'bg-danger-600' : 'bg-accent-600'" x-text="preview.tooBig ? 'Trop lourde' : 'Nouvelle'"></span>
                                <button type="button" class="absolute top-1 right-1 grid size-7 place-items-center rounded-full bg-white/90 text-zinc-700 shadow-sm hover:text-danger-600" x-on:click="remove(index)" aria-label="Retirer cette image"><x-icon name="x" class="size-4" /></button>
                            </div>
                        </template>
                    </div>
                </div>
                @foreach ($errors->get('images.*') as $messages)@foreach ($messages as $m)<p class="field-error">{{ $m }}</p>@endforeach @endforeach
                @error('images')<p class="field-error">{{ $message }}</p>@enderror
            </section>

            @if ($product->exists)
                <section class="card p-5 text-sm text-zinc-600">
                    <dl class="space-y-2">
                        <div class="flex justify-between"><dt>Ventes</dt><dd class="font-semibold text-zinc-900">{{ $product->sales_count }}</dd></div>
                        <div class="flex justify-between"><dt>Note moyenne</dt><dd class="font-semibold text-zinc-900">{{ number_format($product->rating_avg, 1, ',', '') }} ({{ $product->reviews_count }} avis)</dd></div>
                        <div class="flex justify-between"><dt>Créé le</dt><dd>{{ $product->created_at->format('d/m/Y') }}</dd></div>
                    </dl>
                </section>
            @endif
        </div>
    </form>

    @if ($product->exists)
        @foreach ($product->images as $image)
            <form id="primary-{{ $image->id }}" method="POST" action="{{ route('admin.products.images.primary', [$product, $image]) }}" class="hidden">@csrf</form>
            <form id="delete-image-{{ $image->id }}" method="POST" action="{{ route('admin.products.images.destroy', [$product, $image]) }}" class="hidden" data-confirm="Cette image sera définitivement supprimée." data-confirm-title="Supprimer l’image ?" data-confirm-label="Supprimer">@csrf @method('DELETE')</form>
        @endforeach
    @endif
@endsection
