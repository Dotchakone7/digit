@props(['product', 'layout' => 'grid'])
@php
    $inWishlist = app(\App\Services\WishlistService::class)->has($product);
    $url = route('products.show', $product);
@endphp
<article @class([
    'group relative flex overflow-hidden rounded-[var(--radius-card)] bg-white ring-1 ring-zinc-900/5 transition duration-300 hover:shadow-[var(--shadow-lift)]',
    'flex-col' => $layout === 'grid',
    'flex-row' => $layout === 'list',
])>
    <a wire:navigate href="{{ $url }}" @class(['relative block overflow-hidden bg-sand', 'aspect-square' => $layout === 'grid', 'w-36 shrink-0 sm:w-56' => $layout === 'list'])
       aria-label="{{ $product->name }}" tabindex="-1">
        <x-product-image :src="$product->image_url" :alt="$product->name" class="transition duration-500 ease-out group-hover:scale-[1.04]" />
        <div class="absolute top-3 left-3 flex flex-col items-start gap-1.5">
            @if ($product->isOnSale())
                <span class="badge badge-accent">-{{ $product->discountPercent() }} %</span>
            @endif
            @if ($product->isNew())
                <span class="badge bg-white/95 text-brand-900 shadow-sm">Nouveau</span>
            @endif
        </div>
        @unless ($product->isInStock())
            <div class="absolute inset-x-3 bottom-3 rounded-full bg-white/95 py-1.5 text-center text-xs font-semibold text-zinc-600 shadow-sm">Rupture de stock</div>
        @endunless
    </a>

    <button type="button" x-data="wishlistButton('{{ route('wishlist.toggle', $product) }}', {{ $inWishlist ? 'true' : 'false' }}, {{ auth()->check() ? 'true' : 'false' }})"
            @click="toggle" :disabled="busy" :aria-pressed="active.toString()"
            class="absolute top-3 right-3 grid size-9 place-items-center rounded-full bg-white/95 text-brand-900 shadow-sm transition hover:scale-105 hover:text-accent-700"
            aria-label="Ajouter {{ $product->name }} aux favoris">
        <x-icon name="heart" class="size-[18px] transition" x-bind:class="active ? 'fill-accent-600 text-accent-600' : ''" />
    </button>

    <div class="flex flex-1 flex-col gap-2 p-4">
        @if ($product->category)
            <p class="text-xs font-medium tracking-wide text-zinc-500 uppercase">{{ $product->category->name }}</p>
        @endif
        <h3 class="line-clamp-2 font-sans text-[15px] leading-snug font-semibold text-brand-900">
            <a wire:navigate href="{{ $url }}" class="after:absolute after:inset-0 after:content-[''] focus-visible:outline-none">{{ $product->name }}</a>
        </h3>
        @if ($layout === 'list' && $product->short_description)
            <p class="line-clamp-2 hidden text-sm text-zinc-500 sm:block">{{ $product->short_description }}</p>
        @endif
        @if ($product->reviews_count > 0)
            <x-rating :value="$product->rating_avg" :count="$product->reviews_count" size="size-3.5" />
        @endif
        <div class="mt-auto flex items-end justify-between gap-2 pt-1">
            <div>
                <x-price :product="$product" />
                @if ($product->isLowStock())
                    <p class="mt-0.5 text-xs font-medium text-warning-700">Plus que {{ $product->stock }} en stock</p>
                @elseif ($product->isInStock())
                    <p class="mt-0.5 text-xs text-success-700">En stock</p>
                @endif
            </div>
            @if ($product->isInStock())
                @if ($product->hasVariants())
                    <a wire:navigate href="{{ $url }}" class="relative z-10 btn-icon bg-zinc-100 hover:bg-brand-900 hover:text-white" aria-label="Choisir les options de {{ $product->name }}">
                        <x-icon name="arrow-right" class="size-[18px]" />
                    </a>
                @else
                    <button type="button" x-data="quickAdd({{ $product->id }})" @click="add" :disabled="busy"
                            class="relative z-10 btn-icon bg-brand-900 text-white hover:bg-brand-800" aria-label="Ajouter {{ $product->name }} au panier">
                        <x-icon name="bag" class="size-[18px]" x-show="!busy" />
                        <span class="spinner" x-show="busy" x-cloak></span>
                    </button>
                @endif
            @endif
        </div>
    </div>
</article>
