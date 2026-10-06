@props(['title', 'eyebrow' => null, 'link' => null, 'linkLabel' => 'Tout voir'])
<div {{ $attributes->merge(['class' => 'mb-8 flex items-end justify-between gap-4']) }}>
    <div>
        @if ($eyebrow)
            <p class="mb-2 text-xs font-bold tracking-[0.18em] text-accent-700 uppercase">{{ $eyebrow }}</p>
        @endif
        <h2 class="text-2xl font-bold sm:text-3xl">{{ $title }}</h2>
    </div>
    @if ($link)
        <a wire:navigate href="{{ $link }}" class="group hidden shrink-0 items-center gap-1.5 text-sm font-semibold text-brand-900 sm:inline-flex">
            {{ $linkLabel }} <x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-0.5" />
        </a>
    @endif
</div>
