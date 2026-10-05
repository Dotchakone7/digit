@props(['icon' => 'box', 'title', 'text' => null])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-16 text-center']) }}>
    <div class="mb-5 grid size-16 place-items-center rounded-2xl bg-sand text-brand-700">
        <x-icon :name="$icon" class="size-7" />
    </div>
    <h3 class="text-lg font-semibold">{{ $title }}</h3>
    @if ($text)
        <p class="mt-1.5 max-w-sm text-sm text-zinc-500">{{ $text }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-6">{{ $slot }}</div>
    @endif
</div>
