{{-- Refreshes every 20 s while the tab is visible: new orders show up on their own. --}}
<div wire:poll.20s.visible>
    <div class="mb-4 flex flex-wrap items-center justify-end gap-2">
        <span class="mr-auto inline-flex items-center gap-2 text-xs text-zinc-500"><span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-success-500 opacity-60"></span><span class="relative inline-flex size-2 rounded-full bg-success-500"></span></span> Mise à jour automatique</span>
        <a href="{{ route('admin.orders.export', $exportQuery) }}" class="btn btn-secondary"><x-icon name="download" class="size-4" /> Exporter (CSV)</a>
    </div>

    <nav class="scrollbar-none -mx-4 mb-4 flex gap-1 overflow-x-auto px-4 sm:mx-0 sm:px-0" aria-label="Filtrer par statut">
        <button type="button" wire:click="setStatus('')" @class(['shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium transition', 'bg-brand-900 text-white' => $status === '', 'text-zinc-600 hover:bg-white' => $status !== ''])>Toutes <span class="font-normal tabular-nums">{{ $statusCounts->sum() }}</span></button>
        @foreach (\App\Enums\OrderStatus::cases() as $s)
            <button type="button" wire:key="tab-{{ $s->value }}" wire:click="setStatus('{{ $s->value }}')" @class(['shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium transition', 'bg-brand-900 text-white' => $status === $s->value, 'text-zinc-600 hover:bg-white' => $status !== $s->value])>
                {{ $s->label() }} <span class="font-normal tabular-nums">{{ $statusCounts[$s->value] ?? 0 }}</span>
            </button>
        @endforeach
    </nav>

    <div class="card mb-4 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-[1fr_190px_150px_150px_auto]">
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-400" />
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="N°, client, e-mail, téléphone…" class="input pl-9" aria-label="Rechercher une commande">
            <span wire:loading.delay wire:target="search" class="spinner absolute top-1/2 right-3 size-4 -translate-y-1/2 text-brand-700"></span>
        </div>
        <x-form.select name="paymentStatus" wire:model.live="paymentStatus" :options="collect(\App\Enums\PaymentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])" placeholder="Tout paiement" aria-label="Statut du paiement" />
        <input type="date" wire:model.live="from" class="input" aria-label="Du">
        <input type="date" wire:model.live="to" class="input" aria-label="Au">
        @if ($search || $paymentStatus || $from || $to)
            <button type="button" wire:click="resetFilters" class="btn btn-ghost">Effacer</button>
        @endif
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="search,paymentStatus,from,to,setStatus,sortBy,gotoPage,nextPage,previousPage">
            <table class="table-admin">
                <thead>
                    <tr>
                        <th>Commande</th>
                        <x-admin.th-sort field="created_at" :sort-field="$sortField" :sort-direction="$sortDirection">Date</x-admin.th-sort>
                        <th>Client</th>
                        <th>Statut</th>
                        <th>Paiement</th>
                        <x-admin.th-sort field="total" :sort-field="$sortField" :sort-direction="$sortDirection" align="right">Total</x-admin.th-sort>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr wire:key="order-{{ $order->id }}" class="cursor-pointer" x-on:click="if (! $event.target.closest('a')) Livewire.navigate('{{ route('admin.orders.show', $order) }}')">
                            <td>
                                <a wire:navigate href="{{ route('admin.orders.show', $order) }}" class="font-mono text-[13px] font-semibold text-brand-800 hover:underline">{{ $order->number }}</a>
                                @if ($order->created_at->gt(now()->subHour()))<span class="badge badge-accent ml-1 text-[10px]">Nouveau</span>@endif
                                <span class="block text-xs text-zinc-500">{{ $order->items_count }} article(s)</span>
                            </td>
                            <td class="whitespace-nowrap text-zinc-600">{{ $order->created_at->format('d/m/Y H:i') }}<span class="block text-xs text-zinc-400">{{ $order->created_at->diffForHumans() }}</span></td>
                            <td><span class="block font-medium text-zinc-900">{{ $order->customer_name }}</span><span class="text-xs text-zinc-500">{{ $order->customer_phone }}</span></td>
                            <td><x-status-badge :status="$order->status" /></td>
                            <td><x-status-badge :status="$order->payment_status" /><span class="mt-0.5 block text-xs text-zinc-500">{{ config("payments.gateways.{$order->payment_method}.label", $order->payment_method) }}</span></td>
                            <td class="text-right font-semibold whitespace-nowrap tabular-nums">{{ money($order->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state icon="box" title="Aucune commande" text="Aucune commande ne correspond à ces critères." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $orders->links() }}</div>
</div>
