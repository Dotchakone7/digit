@props(['status'])
<span {{ $attributes->merge(['class' => 'badge badge-'.$status->tone()]) }}>
    <span class="size-1.5 rounded-full bg-current opacity-70" aria-hidden="true"></span>{{ $status->label() }}
</span>
