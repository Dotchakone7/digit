@props(['product', 'size' => 'md'])
<div {{ $attributes->merge(['class' => 'flex flex-wrap items-baseline gap-x-2 gap-y-0.5']) }}>
    <span @class(['font-semibold text-brand-900 tabular-nums',
        'text-base' => $size === 'md', 'text-3xl font-bold font-display' => $size === 'lg', 'text-sm' => $size === 'sm'])>
        {{ money($product->currentPrice()) }}
    </span>
    @if ($product->isOnSale())
        <span @class(['text-zinc-500 line-through tabular-nums', 'text-sm' => $size !== 'lg', 'text-lg' => $size === 'lg'])>
            <span class="sr-only">Prix initial :</span>{{ money($product->price) }}
        </span>
    @endif
</div>
