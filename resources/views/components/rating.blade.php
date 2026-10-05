@props(['value' => 0, 'count' => null, 'size' => 'size-4'])
<div {{ $attributes->merge(['class' => 'flex items-center gap-1.5']) }}>
    <div class="flex items-center text-amber-400" role="img" aria-label="Note : {{ number_format($value, 1, ',', '') }} sur 5">
        @for ($i = 1; $i <= 5; $i++)
            <x-icon name="star" :filled="$value >= $i - 0.25" @class([$size, 'text-zinc-200' => $value < $i - 0.75]) />
        @endfor
    </div>
    @if ($count !== null)
        <span class="text-xs text-zinc-500">({{ $count }})</span>
    @endif
</div>
