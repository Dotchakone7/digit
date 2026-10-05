@extends('layouts.admin')

@section('title', 'Tableau de bord')

@section('content')
    <x-admin.page-header title="Tableau de bord" :subtitle="'Activité de la boutique · '.now()->translatedFormat('l d F Y')">
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nouveau produit</a>
    </x-admin.page-header>

    @if (array_sum($todo))
        <div class="mb-6 flex flex-wrap gap-2">
            @if ($todo['payments_to_check'])<a href="{{ route('admin.payments.index', ['status' => 'processing']) }}" class="badge badge-info px-3 py-1.5 text-[13px]"><x-icon name="card" class="size-4" /> {{ $todo['payments_to_check'] }} paiement(s) à vérifier</a>@endif
            @if ($todo['pending_reviews'])<a href="{{ route('admin.reviews.index') }}" class="badge badge-warning px-3 py-1.5 text-[13px]"><x-icon name="star" class="size-4" /> {{ $todo['pending_reviews'] }} avis à modérer</a>@endif
            @if ($todo['open_returns'])<a href="{{ route('admin.returns.index') }}" class="badge badge-danger px-3 py-1.5 text-[13px]"><x-icon name="refresh" class="size-4" /> {{ $todo['open_returns'] }} retour(s) en cours</a>@endif
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat-card label="Chiffre d’affaires (mois)" :value="money($kpis['revenue_month'])" icon="wallet" :trend="$kpis['revenue_trend']" hint="Commandes payées ce mois-ci" />
        <x-admin.stat-card label="Chiffre d’affaires total" :value="money($kpis['revenue_total'])" icon="chart" hint="Commandes payées, hors annulations" />
        <x-admin.stat-card label="Commandes" :value="number_format($kpis['orders'], 0, ',', ' ')" icon="box" :hint="$kpis['orders_pending'].' en attente de traitement'" :tone="$kpis['orders_pending'] ? 'warning' : 'brand'" :href="route('admin.orders.index', ['status' => 'pending'])" />
        <x-admin.stat-card label="Clients" :value="number_format($kpis['customers'], 0, ',', ' ')" icon="users" :hint="$kpis['products'].' produits en ligne · '.$kpis['low_stock'].' en stock faible'" :href="route('admin.users.index', ['role' => 'customer'])" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        {{-- Sales chart: single series, no legend (the title names it), hover tooltip + table view --}}
        @php
            $max = max(1, collect($salesChart)->max('total'));
            $periodTotal = collect($salesChart)->sum('total');
            $w = 720; $h = 220; $gap = 2; $barW = ($w / count($salesChart)) - $gap;
            $ticks = collect([0, 0.5, 1])->map(fn ($r) => ['y' => $h - $r * $h, 'v' => (int) round($max * $r)]);
        @endphp
        <section class="card p-5 xl:col-span-2" x-data="{ hover: null, table: false }" aria-labelledby="sales-title">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 id="sales-title" class="text-base font-semibold text-zinc-900">Ventes des 30 derniers jours</h2>
                    <p class="text-sm text-zinc-500">CA encaissé par jour · total <span class="font-semibold text-zinc-900">{{ money($periodTotal) }}</span></p>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" @click="table = !table" x-text="table ? 'Voir le graphique' : 'Voir le tableau'"></button>
            </div>
            <div class="relative mt-5" x-show="!table">
                <svg viewBox="0 -14 {{ $w + 80 }} {{ $h + 42 }}" class="h-auto w-full" role="img" aria-label="Histogramme du chiffre d’affaires quotidien sur 30 jours, total {{ money($periodTotal) }}">
                    @foreach ($ticks as $tick)
                        <line x1="70" x2="{{ $w + 72 }}" y1="{{ $tick['y'] }}" y2="{{ $tick['y'] }}" stroke="#e4e4e7" stroke-width="1" @if ($tick['v'] > 0) stroke-dasharray="3 4" @endif />
                        <text x="62" y="{{ $tick['y'] + 4 }}" text-anchor="end" font-size="11" fill="#71717a">{{ number_format($tick['v'] / 1000, 0, ',', ' ') }} k</text>
                    @endforeach
                    @foreach ($salesChart as $i => $day)
                        @php($bh = $day['total'] > 0 ? max(4, $day['total'] / $max * $h) : 0)
                        @php($x = 70 + $i * ($barW + $gap) + $gap / 2)
                        <g @mouseenter="hover = {{ $i }}" @mouseleave="hover = null">
                            <rect x="{{ $x }}" y="0" width="{{ $barW + $gap }}" height="{{ $h }}" fill="transparent" />
                            @if ($bh > 0)
                                <path d="M{{ $x }},{{ $h }} V{{ $h - $bh + 4 }} Q{{ $x }},{{ $h - $bh }} {{ $x + 4 }},{{ $h - $bh }} H{{ $x + $barW - 4 }} Q{{ $x + $barW }},{{ $h - $bh }} {{ $x + $barW }},{{ $h - $bh + 4 }} V{{ $h }} Z"
                                      fill="var(--color-chart-1)" :opacity="hover === null || hover === {{ $i }} ? 1 : 0.45" />
                            @endif
                        </g>
                        @if ($i % 5 === 0 || $loop->last)
                            <text x="{{ $x + $barW / 2 }}" y="{{ $h + 18 }}" text-anchor="{{ $loop->last ? 'end' : 'middle' }}" font-size="11" fill="#71717a">{{ $day['label'] }}</text>
                        @endif
                    @endforeach
                </svg>
                @foreach ($salesChart as $i => $day)
                    <div x-show="hover === {{ $i }}" x-cloak class="pointer-events-none absolute -top-2 z-10 -translate-x-1/2 rounded-lg bg-zinc-900 px-3 py-2 text-xs whitespace-nowrap text-white shadow-lg"
                         style="left: {{ min(88, max(12, (70 + $i * ($barW + $gap) + $barW / 2) / ($w + 80) * 100)) }}%">
                        <p class="font-semibold">{{ $day['date']->translatedFormat('l d F') }}</p>
                        <p class="text-zinc-300">{{ money($day['total']) }} · {{ $day['count'] }} commande(s)</p>
                    </div>
                @endforeach
            </div>
            <div x-show="table" x-cloak class="mt-4 max-h-72 overflow-y-auto">
                <table class="table-admin">
                    <thead><tr><th>Jour</th><th class="text-right">Commandes payées</th><th class="text-right">Chiffre d’affaires</th></tr></thead>
                    <tbody>
                        @foreach (array_reverse($salesChart) as $day)
                            <tr><td>{{ $day['date']->translatedFormat('D d M') }}</td><td class="text-right tabular-nums">{{ $day['count'] }}</td><td class="text-right tabular-nums">{{ money($day['total']) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card p-5" aria-labelledby="top-title">
            <h2 id="top-title" class="text-base font-semibold text-zinc-900">Meilleures ventes</h2>
            <ol class="mt-4 space-y-3">
                @forelse ($topProducts as $i => $item)
                    <li class="flex items-center gap-3">
                        <span class="grid size-7 shrink-0 place-items-center rounded-full bg-zinc-100 text-xs font-bold text-zinc-600">{{ $i + 1 }}</span>
                        <span class="min-w-0 flex-1"><span class="block truncate text-sm font-medium text-zinc-900">{{ $item->product_name }}</span><span class="text-xs text-zinc-500">{{ $item->units }} vendus</span></span>
                        <span class="text-sm font-semibold tabular-nums">{{ money((int) $item->revenue) }}</span>
                    </li>
                @empty
                    <li class="text-sm text-zinc-500">Aucune vente pour le moment.</li>
                @endforelse
            </ol>
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="card overflow-hidden xl:col-span-2">
            <div class="flex items-center justify-between px-5 py-4">
                <h2 class="text-base font-semibold text-zinc-900">Commandes récentes</h2>
                <a href="{{ route('admin.orders.index') }}" class="text-sm font-medium text-brand-700 hover:underline">Toutes les commandes</a>
            </div>
            <div class="overflow-x-auto">
                <table class="table-admin">
                    <thead><tr><th>Commande</th><th>Client</th><th>Statut</th><th>Paiement</th><th class="text-right">Total</th></tr></thead>
                    <tbody>
                        @forelse ($recentOrders as $order)
                            <tr class="cursor-pointer" onclick="window.location='{{ route('admin.orders.show', $order) }}'">
                                <td><a href="{{ route('admin.orders.show', $order) }}" class="font-mono text-[13px] font-semibold text-brand-800 hover:underline">{{ $order->number }}</a><p class="text-xs text-zinc-500">{{ $order->created_at->diffForHumans() }}</p></td>
                                <td class="text-zinc-700">{{ $order->customer_name }}</td>
                                <td><x-status-badge :status="$order->status" /></td>
                                <td><x-status-badge :status="$order->payment_status" /></td>
                                <td class="text-right font-semibold tabular-nums">{{ money($order->total) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-10 text-center text-zinc-500">Aucune commande.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="space-y-6">
            <section class="card p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-zinc-900">Stock faible</h2>
                    <a href="{{ route('admin.products.index', ['stock' => 'low']) }}" class="text-sm font-medium text-brand-700 hover:underline">Gérer</a>
                </div>
                <ul class="mt-4 space-y-3">
                    @forelse ($lowStockProducts as $product)
                        <li class="flex items-center gap-3">
                            <span class="size-9 shrink-0 overflow-hidden rounded-lg bg-zinc-100"><x-product-image :src="$product->image_url" alt="" /></span>
                            <a href="{{ route('admin.products.edit', $product) }}" class="min-w-0 flex-1 truncate text-sm text-zinc-800 hover:underline">{{ $product->name }}</a>
                            <span @class(['badge', 'badge-danger' => $product->stock === 0, 'badge-warning' => $product->stock > 0])>{{ $product->stock === 0 ? 'Épuisé' : $product->stock.' restant(s)' }}</span>
                        </li>
                    @empty
                        <li class="flex items-center gap-2 text-sm text-success-700"><x-icon name="check-circle" class="size-4" /> Tous les stocks sont suffisants.</li>
                    @endforelse
                </ul>
            </section>

            <section class="card p-5">
                <h2 class="text-base font-semibold text-zinc-900">Dernières activités</h2>
                <ul class="mt-4 space-y-3">
                    @forelse ($activity as $event)
                        <li class="flex gap-3 text-sm">
                            <x-status-badge :status="$event->to_status" class="shrink-0" />
                            <span class="min-w-0 text-zinc-600">
                                <a href="{{ route('admin.orders.show', $event->order->number) }}" class="font-mono text-xs font-semibold text-zinc-900 hover:underline">{{ $event->order->number }}</a>
                                <span class="block text-xs text-zinc-500">{{ $event->created_at->diffForHumans() }}{{ $event->user ? ' · '.$event->user->name : '' }}</span>
                            </span>
                        </li>
                    @empty
                        <li class="text-sm text-zinc-500">Aucune activité.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>
@endsection
