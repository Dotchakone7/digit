@props(['title', 'subtitle' => null, 'back' => null])
<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="mb-1 inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900"><x-icon name="arrow-left" class="size-4" /> Retour</a>
        @endif
        <h1 class="text-2xl font-bold text-zinc-900">{{ $title }}</h1>
        @if ($subtitle)<p class="mt-1 text-sm text-zinc-500">{{ $subtitle }}</p>@endif
    </div>
    @if ($slot->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>
