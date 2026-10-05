@props(['name', 'label' => null, 'value' => null, 'hint' => null, 'rows' => 4])
@php
    $id = $attributes->get('id', str_replace(['[', ']', '.'], '_', $name));
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->has($key);
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }}@if ($attributes->has('required'))<span class="text-danger-600" aria-hidden="true"> *</span>@endif</label>
    @endif
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}"
              {{ $attributes->except('class')->class(['input', 'input-error' => $hasError]) }}
              @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif>{{ old($key, $value) }}</textarea>
    @error($key)
        <p id="{{ $id }}-error" class="field-error">{{ $message }}</p>
    @else
        @if ($hint)<p class="field-hint">{{ $hint }}</p>@endif
    @enderror
</div>
