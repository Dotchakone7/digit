@props(['label', 'value', 'icon', 'hint' => null, 'tone' => 'brand', 'href' => null, 'trend' => null])
@php
    $body = view()->make('components.admin.stat-card-body', compact('label', 'value', 'icon', 'hint', 'tone', 'trend'))->render();
@endphp
@if ($href)
    <a wire:navigate href="{{ $href }}" class="card block p-5 transition hover:ring-zinc-300">{!! $body !!}</a>
@else
    <div class="card p-5">{!! $body !!}</div>
@endif
