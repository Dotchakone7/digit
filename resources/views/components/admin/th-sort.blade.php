@props(['field', 'sortField' => '', 'sortDirection' => 'desc', 'align' => 'left'])
@php($active = $sortField === $field)
<th @class(['text-right' => $align === 'right']) aria-sort="{{ $active ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}">
    <button type="button" wire:click="sortBy('{{ $field }}')" @class(['inline-flex items-center gap-1 uppercase tracking-wide transition hover:text-zinc-900', 'text-zinc-900' => $active, 'flex-row-reverse' => $align === 'right'])>
        {{ $slot }}
        <x-icon :name="$active && $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" @class(['size-3.5', 'opacity-30' => ! $active]) />
    </button>
</th>
