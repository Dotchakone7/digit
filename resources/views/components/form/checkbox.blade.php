@props(['name', 'label', 'checked' => false, 'value' => '1', 'hint' => null])
@php
    $id = $attributes->get('id', str_replace(['[', ']', '.'], '_', $name));
    $key = str_replace(['[', ']'], ['.', ''], $name);
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="flex cursor-pointer items-start gap-3">
        <input type="hidden" name="{{ $name }}" value="0">
        <input id="{{ $id }}" type="checkbox" name="{{ $name }}" value="{{ $value }}" class="checkbox mt-0.5"
               @checked(old($key, $checked)) {{ $attributes->except('class') }}>
        <span>
            <span class="text-sm font-medium text-zinc-800">{{ $label }}</span>
            @if ($hint)<span class="block text-xs text-zinc-500">{{ $hint }}</span>@endif
        </span>
    </label>
    @error($key)<p class="field-error">{{ $message }}</p>@enderror
</div>
