@extends('layouts.admin')

@section('title', 'Commandes')

@section('content')
    <x-admin.page-header title="Commandes" :subtitle="$orders->total().' commande(s)'">
        <a href="{{ route('admin.orders.export', request()->query()) }}" class="btn btn-secondary"><x-icon name="download" class="size-4" /> Exporter (CSV)</a>
    </x-admin.page-header>

    <nav class="scrollbar-none -mx-4 mb-4 flex gap-1 overflow-x-auto px-4 sm:mx-0 sm:px-0" aria-label="Filtrer par statut">
        @php($current = $filters['status'] ?? null)
        <a href="{{ route('admin.orders.index', \Illuminate\Support\Arr::except(request()->query(), ['status', 'page'])) }}" @class(['shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium', 'bg-brand-900 text-white' => ! $current, 'text-zinc-600 hover:bg-white' => $current])>Toutes <span class="font-normal tabular-nums">{{ $statusCounts->sum() }}</span></a>
        @foreach (\App\Enums\OrderStatus::cases() as $status)
            <a href="{{ route('admin.orders.index', array_merge(\Illuminate\Support\Arr::except(request()->query(), 'page'), ['status' => $status->value])) }}" @class(['shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium', 'bg-brand-900 text-white' => $current === $status->value, 'text-zinc-600 hover:bg-white' => $current !== $status->value])>
                {{ $status->label() }} <span class="font-normal tabular-nums">{{ $statusCounts[$status->value] ?? 0 }}</span>
            </a>
        @endforeach
    </nav>

    <form method="GET" class="card mb-4 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-[1fr_180px_150px_150px_170px_auto]">
        @if ($current)<input type="hidden" name="status" value="{{ $current }}">@endif
        <div class="relative"><x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-400" /><input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="N°, client, e-mail, téléphone…" class="input pl-9" aria-label="Rechercher"></div>
        <x-form.select name="payment_status" :options="collect(\App\Enums\PaymentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])" :value="$filters['payment_status'] ?? ''" placeholder="Tout paiement" aria-label="Statut du paiement" />
        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="input" aria-label="Du">
        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="input" aria-label="Au">
        <x-form.select name="sort" :options="['newest' => 'Plus récentes', 'oldest' => 'Plus anciennes', 'total_desc' => 'Montant décroissant', 'total_asc' => 'Montant croissant']" :value="$filters['sort'] ?? 'newest'" aria-label="Tri" />
        <div class="flex gap-2"><button class="btn btn-secondary">Filtrer</button>@if (array_filter(\Illuminate\Support\Arr::except($filters, 'status')))<a href="{{ route('admin.orders.index', array_filter(['status' => $current])) }}" class="btn btn-ghost">Effacer</a>@endif</div>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th>Commande</th><th>Date</th><th>Client</th><th>Articles</th><th>Statut</th><th>Paiement</th><th class="text-right">Total</th></tr></thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr class="cursor-pointer" onclick="window.location='{{ route('admin.orders.show', $order) }}'">
                            <td><a href="{{ route('admin.orders.show', $order) }}" class="font-mono text-[13px] font-semibold text-brand-800 hover:underline">{{ $order->number }}</a></td>
                            <td class="whitespace-nowrap text-zinc-600">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td><span class="block font-medium text-zinc-900">{{ $order->customer_name }}</span><span class="text-xs text-zinc-500">{{ $order->customer_phone }}</span></td>
                            <td class="text-zinc-600">{{ $order->items_count }}</td>
                            <td><x-status-badge :status="$order->status" /></td>
                            <td><x-status-badge :status="$order->payment_status" /><span class="mt-0.5 block text-xs text-zinc-500">{{ config("payments.gateways.{$order->payment_method}.label", $order->payment_method) }}</span></td>
                            <td class="text-right font-semibold whitespace-nowrap tabular-nums">{{ money($order->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty-state icon="box" title="Aucune commande" text="Aucune commande ne correspond à ces critères." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $orders->links() }}</div>
@endsection
