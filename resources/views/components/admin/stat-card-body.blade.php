<div class="flex items-start justify-between gap-3">
    <p class="text-sm font-medium text-zinc-500">{{ $label }}</p>
    <span @class(['grid size-9 place-items-center rounded-lg',
        'bg-brand-50 text-brand-700' => $tone === 'brand',
        'bg-warning-50 text-warning-700' => $tone === 'warning',
        'bg-danger-50 text-danger-700' => $tone === 'danger',
        'bg-success-50 text-success-700' => $tone === 'success'])><x-icon :name="$icon" class="size-[18px]" /></span>
</div>
<p class="mt-2 text-2xl font-bold tracking-tight text-zinc-900 tabular-nums">{{ $value }}</p>
@if ($trend !== null)
    <p @class(['mt-1 text-xs font-medium', 'text-success-700' => $trend >= 0, 'text-danger-700' => $trend < 0])>
        <span aria-hidden="true">{{ $trend >= 0 ? '▲' : '▼' }}</span> {{ $trend >= 0 ? '+' : '−' }}{{ abs($trend) }} % <span class="font-normal text-zinc-500">vs mois précédent</span>
    </p>
@elseif ($hint)
    <p class="mt-1 text-xs text-zinc-500">{{ $hint }}</p>
@endif
