@extends('layouts.shop')

@section('robots', 'noindex, nofollow')

@section('content')
    @php
        $links = [
            ['account.dashboard', 'Tableau de bord', 'home', 'account.dashboard'],
            ['account.orders.index', 'Mes commandes', 'box', 'account.orders.*'],
            ['account.addresses.index', 'Mes adresses', 'map-pin', 'account.addresses.*'],
            ['account.wishlist', 'Mes favoris', 'heart', 'account.wishlist'],
            ['account.reviews', 'Mes avis', 'star', 'account.reviews*'],
            ['account.profile.edit', 'Mon profil', 'user', 'account.profile.*'],
            ['account.password.edit', 'Sécurité', 'lock', 'account.password.*'],
        ];
    @endphp
    <div class="container-shop py-8 lg:py-12">
        <div class="grid gap-8 lg:grid-cols-[260px_1fr]">
            <aside>
                <div class="mb-4 hidden items-center gap-3 lg:flex">
                    <span class="grid size-12 place-items-center rounded-2xl bg-brand-900 font-bold text-white">{{ auth()->user()->initials() }}</span>
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-brand-900">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-zinc-500">Client depuis {{ auth()->user()->created_at->translatedFormat('F Y') }}</p>
                    </div>
                </div>
                <nav aria-label="Espace client" class="scrollbar-none -mx-4 flex gap-2 overflow-x-auto px-4 lg:mx-0 lg:flex-col lg:gap-1 lg:px-0">
                    @foreach ($links as [$route, $label, $icon, $pattern])
                        <a href="{{ route($route) }}" @class([
                            'flex shrink-0 items-center gap-3 rounded-full px-4 py-2 text-sm font-medium transition lg:rounded-xl lg:py-2.5',
                            'bg-brand-900 text-white' => request()->routeIs($pattern),
                            'bg-white text-zinc-600 ring-1 ring-zinc-200 hover:text-brand-900 lg:bg-transparent lg:ring-0 lg:hover:bg-white' => ! request()->routeIs($pattern),
                        ]) @if (request()->routeIs($pattern)) aria-current="page" @endif>
                            <x-icon :name="$icon" class="size-[18px]" /> {{ $label }}
                        </a>
                    @endforeach
                    <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                        @csrf
                        <button type="submit" class="flex items-center gap-3 rounded-full bg-white px-4 py-2 text-sm font-medium text-zinc-600 ring-1 ring-zinc-200 transition hover:text-danger-700 lg:w-full lg:rounded-xl lg:bg-transparent lg:py-2.5 lg:ring-0 lg:hover:bg-white">
                            <x-icon name="logout" class="size-[18px]" /> Déconnexion
                        </button>
                    </form>
                </nav>
            </aside>
            <section class="min-w-0">
                @yield('account')
            </section>
        </div>
    </div>
@endsection
