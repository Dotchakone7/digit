@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'hint' => null, 'icon' => null])
@php
    $id = $attributes->get('id', str_replace(['[', ']', '.'], '_', $name));
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->has($key);
@endphp
<div {{ $attributes->only('class')->merge(['class' => '']) }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }}@if ($attributes->has('required'))<span class="text-danger-600" aria-hidden="true"> *</span>@endif</label>
    @endif
    <div class="relative">
        @if ($icon)
            <x-icon :name="$icon" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-zinc-400" />
        @endif
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
               @if ($type !== 'password') value="{{ old($key, $value) }}" @endif
               {{ $attributes->except('class')->class(['input', 'input-error' => $hasError, 'pl-10' => $icon]) }}
               @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($hint) aria-describedby="{{ $id }}-hint" @endif>
    </div>
    @error($key)
        <p id="{{ $id }}-error" class="field-error">{{ $message }}</p>
    @else
        @if ($hint)<p id="{{ $id }}-hint" class="field-hint">{{ $hint }}</p>@endif
    @enderror
</div>
