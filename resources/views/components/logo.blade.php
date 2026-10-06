@props(['variant' => 'dark'])
@php($logo = $variant === 'light' ? (config('shop.logo_dark') ?: config('shop.logo')) : config('shop.logo'))
<a wire:navigate href="{{ route('home') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5 rounded-lg']) }} aria-label="{{ config('shop.name') }} — accueil">
    @if ($logo)
        <img src="{{ asset($logo) }}" alt="{{ config('shop.name') }}" class="h-9 w-auto">
    @else
        <span @class(['grid size-9 place-items-center rounded-xl font-display text-lg font-extrabold',
            'bg-brand-900 text-white' => $variant === 'dark', 'bg-white text-brand-900' => $variant === 'light'])>
            {{ mb_strtoupper(mb_substr(config('shop.name'), 0, 1)) }}
        </span>
        <span @class(['font-display text-xl font-extrabold tracking-tight',
            'text-brand-900' => $variant === 'dark', 'text-white' => $variant === 'light'])>{{ config('shop.name') }}<span class="text-accent-500">.</span></span>
    @endif
</a>
