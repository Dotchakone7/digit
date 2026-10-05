@props(['items' => []])
<nav aria-label="Fil d’Ariane" {{ $attributes->merge(['class' => 'text-sm']) }}>
    <ol class="flex flex-wrap items-center gap-1.5 text-zinc-500">
        <li><a href="{{ route('home') }}" class="transition hover:text-brand-900">Accueil</a></li>
        @foreach ($items as $label => $url)
            <li class="flex items-center gap-1.5" @if ($loop->last) aria-current="page" @endif>
                <x-icon name="chevron-right" class="size-3.5 text-zinc-300" />
                @if ($url && ! $loop->last)
                    <a href="{{ $url }}" class="transition hover:text-brand-900">{{ $label }}</a>
                @else
                    <span class="line-clamp-1 font-medium text-zinc-800">{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
@php($trail = ['Accueil' => route('home')] + $items)
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => collect($trail)->keys()->map(fn ($label, $i) => [
        '@type' => 'ListItem', 'position' => $i + 1, 'name' => $label, 'item' => $trail[$label] ?: url()->current(),
    ])->values(),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
</script>
