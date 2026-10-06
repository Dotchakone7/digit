<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Tableau de bord') · Administration {{ config('shop.name') }}</title>
    <link rel="icon" href="{{ asset(config('shop.favicon')) }}">
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    @livewireStyles
    @livewireScriptConfig
</head>
<body class="min-h-screen" x-data="{ sidebar: false }">
    @php
        $pendingOrders = \App\Models\Order::query()->where('status', 'pending')->count();
        $nav = [
            ['Pilotage', [
                ['admin.dashboard', 'Tableau de bord', 'chart', 'admin.dashboard', 'dashboard.view', null],
            ]],
            ['Ventes', [
                ['admin.orders.index', 'Commandes', 'box', 'admin.orders.*', 'orders.manage', $pendingOrders ?: null],
                ['admin.payments.index', 'Paiements', 'card', 'admin.payments.*', 'payments.manage', null],
                ['admin.returns.index', 'Retours', 'refresh', 'admin.returns.*', 'returns.manage', null],
                ['admin.coupons.index', 'Promotions', 'percent', 'admin.coupons.*', 'coupons.manage', null],
            ]],
            ['Catalogue', [
                ['admin.products.index', 'Produits', 'tag', 'admin.products.*', 'catalog.manage', null],
                ['admin.categories.index', 'Catégories', 'folder', 'admin.categories.*', 'catalog.manage', null],
                ['admin.reviews.index', 'Avis clients', 'star', 'admin.reviews.*', 'reviews.moderate', null],
            ]],
            ['Boutique', [
                ['admin.users.index', 'Utilisateurs', 'users', 'admin.users.*', 'customers.view', null],
                ['admin.shipping-methods.index', 'Livraisons', 'truck', 'admin.shipping-methods.*', 'orders.manage', null],
                ['admin.newsletter.index', 'Newsletter', 'mail', 'admin.newsletter.*', 'settings.manage', null],
                ['admin.settings.edit', 'Paramètres', 'settings', 'admin.settings.*', 'settings.manage', null],
            ]],
        ];
    @endphp

    {{-- Sidebar --}}
    <div x-show="sidebar" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-brand-950/50 lg:hidden" @click="sidebar = false"></div>
    <aside class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-brand-950 transition-transform duration-300 lg:translate-x-0" :class="sidebar && 'translate-x-0'" aria-label="Navigation de l’administration">
        <div class="flex h-16 items-center justify-between px-5">
            <x-logo variant="light" />
            <span class="rounded-md bg-white/10 px-1.5 py-0.5 text-[10px] font-bold tracking-wider text-brand-200 uppercase">Admin</span>
        </div>
        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4">
            @foreach ($nav as [$group, $items])
                @php($visible = collect($items)->filter(fn ($i) => auth()->user()->can($i[4])))
                @if ($visible->isNotEmpty())
                    <div>
                        <p class="mb-1.5 px-3 text-[11px] font-semibold tracking-wider text-brand-400 uppercase">{{ $group }}</p>
                        @foreach ($visible as [$route, $label, $icon, $pattern, $ability, $badge])
                            <a wire:navigate href="{{ route($route) }}" @class(['nav-admin-link', 'is-active' => request()->routeIs($pattern)]) @if (request()->routeIs($pattern)) aria-current="page" @endif>
                                <x-icon :name="$icon" class="size-[18px]" /> <span class="flex-1">{{ $label }}</span>
                                @if ($badge)<span class="rounded-full bg-accent-600 px-1.5 text-[11px] font-bold text-white">{{ $badge }}</span>@endif
                            </a>
                        @endforeach
                    </div>
                @endif
            @endforeach
        </nav>
        <div class="border-t border-white/10 p-3">
            <a href="{{ route('home') }}" target="_blank" class="nav-admin-link"><x-icon name="external" class="size-[18px]" /> Voir la boutique</a>
        </div>
    </aside>

    <div class="lg:pl-64">
        <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-zinc-200 bg-white/90 px-4 backdrop-blur sm:px-6">
            <button type="button" class="btn-icon -ml-2 lg:hidden" @click="sidebar = true" aria-label="Ouvrir le menu"><x-icon name="menu" /></button>
            @can('orders.manage')
                <form action="{{ route('admin.orders.index') }}" method="GET" class="relative hidden w-full max-w-sm sm:block" role="search">
                    <label for="admin-search" class="sr-only">Rechercher une commande</label>
                    <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-400" />
                    <input id="admin-search" name="q" type="search" placeholder="N° de commande, client, téléphone…" class="input bg-zinc-50 pl-9">
                </form>
            @endcan
            <div class="ml-auto flex items-center gap-3" x-data="{ open: false }" @click.outside="open = false">
                <span class="hidden text-right sm:block">
                    <span class="block text-sm font-semibold text-zinc-900">{{ auth()->user()->name }}</span>
                    <span class="block text-xs text-zinc-500">{{ auth()->user()->roleSlug()->label() }}</span>
                </span>
                <button type="button" class="relative grid size-9 place-items-center rounded-full bg-brand-900 text-xs font-bold text-white" @click="open = !open" aria-haspopup="true" :aria-expanded="open.toString()" aria-label="Menu du compte">{{ auth()->user()->initials() }}</button>
                <div x-show="open" x-cloak x-transition class="absolute top-14 right-4 w-52 rounded-xl bg-white p-1.5 shadow-lg ring-1 ring-zinc-200">
                    <a href="{{ route('account.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-zinc-50"><x-icon name="user" class="size-4" /> Mon compte client</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-danger-700 hover:bg-danger-50"><x-icon name="logout" class="size-4" /> Déconnexion</button></form>
                </div>
            </div>
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            @yield('content')
        </main>
    </div>

    <x-confirm-dialog />
    <x-toasts />
</body>
</html>
