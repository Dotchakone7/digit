@props(['name', 'label' => null, 'options' => [], 'value' => null, 'placeholder' => null, 'hint' => null])
@php
    $id = $attributes->get('id', str_replace(['[', ']', '.'], '_', $name));
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->has($key);
    $selected = (string) old($key, $value instanceof \BackedEnum ? $value->value : $value);
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }}@if ($attributes->has('required'))<span class="text-danger-600" aria-hidden="true"> *</span>@endif</label>
    @endif
    <div class="relative">
        <select id="{{ $id }}" name="{{ $name }}" {{ $attributes->except('class')->class(['input appearance-none pr-10', 'input-error' => $hasError]) }}
                @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif>
            @if ($placeholder !== null)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>
        <x-icon name="chevron-down" class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-zinc-400" />
    </div>
    @error($key)
        <p id="{{ $id }}-error" class="field-error">{{ $message }}</p>
    @else
        @if ($hint)<p class="field-hint">{{ $hint }}</p>@endif
    @enderror
</div>
