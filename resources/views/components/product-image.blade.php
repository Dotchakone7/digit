@props(['src' => null, 'alt' => '', 'sizes' => null])
@if ($src)
    <img src="{{ $src }}" alt="{{ $alt }}" loading="lazy" decoding="async" {{ $attributes->merge(['class' => 'size-full object-cover']) }}>
@else
    <div {{ $attributes->merge(['class' => 'grid size-full place-items-center bg-gradient-to-br from-sand to-zinc-100 text-zinc-300']) }} role="img" aria-label="{{ $alt }}">
        <x-icon name="image" class="size-10" />
    </div>
@endif
