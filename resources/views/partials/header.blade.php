@if ($announcement = setting('announcement'))
    <div class="bg-brand-900 px-4 py-2 text-center text-xs font-medium text-brand-100 sm:text-[13px]">{{ $announcement }}</div>
@endif

<header x-data="{ mobileMenu: false, mobileSearch: false, scrolled: false }" @scroll.window.passive="scrolled = window.scrollY > 8"
        class="sticky top-0 z-50 border-b bg-white/90 backdrop-blur-xl transition-shadow duration-300"
        :class="scrolled ? 'border-zinc-200/80 shadow-[0_8px_24px_-18px_rgb(10_29_55/0.35)]' : 'border-transparent'">
    <div class="container-shop flex h-16 items-center gap-3 lg:h-[72px] lg:gap-8">
        <button type="button" class="btn-icon -ml-2 lg:hidden" @click="mobileMenu = true" aria-label="Ouvrir le menu" :aria-expanded="mobileMenu.toString()">
            <x-icon name="menu" />
        </button>

        <x-logo class="shrink-0" />

        <nav class="hidden items-center gap-1 lg:flex" aria-label="Navigation principale">
            <a href="{{ route('catalog.index') }}" class="rounded-full px-3.5 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 hover:text-brand-900">Boutique</a>
            @if ($navCategories->isNotEmpty())
                <div x-data="{ open: false }" class="relative" @mouseenter="open = true" @mouseleave="open = false" @keydown.escape="open = false">
                    <button type="button" class="flex items-center gap-1 rounded-full px-3.5 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 hover:text-brand-900"
                            @click="open = !open" :aria-expanded="open.toString()" aria-haspopup="true">
                        Catégories <x-icon name="chevron-down" class="size-4 transition" x-bind:class="open && 'rotate-180'" />
                    </button>
                    <div x-show="open" x-cloak x-transition.opacity.duration.150ms class="absolute top-full left-0 w-[520px] pt-2">
                        <div class="grid grid-cols-2 gap-1 rounded-2xl bg-white p-3 shadow-[var(--shadow-lift)] ring-1 ring-zinc-900/5">
                            @foreach ($navCategories as $category)
                                <a href="{{ route('catalog.category', $category['slug']) }}" class="group flex items-center gap-3 rounded-xl p-2.5 transition hover:bg-zinc-50">
                                    <span class="size-11 shrink-0 overflow-hidden rounded-lg bg-sand">
                                        <x-product-image :src="$category['image_url']" :alt="''" />
                                    </span>
                                    <span>
                                        <span class="block text-sm font-semibold text-brand-900">{{ $category['name'] }}</span>
                                        @if (! empty($category['children']))
                                            <span class="line-clamp-1 text-xs text-zinc-500">{{ collect($category['children'])->pluck('name')->implode(', ') }}</span>
                                        @endif
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
            <a href="{{ route('catalog.index', ['on_sale' => 1]) }}" class="rounded-full px-3.5 py-2 text-sm font-medium text-accent-700 transition hover:bg-accent-50">Promotions</a>
            <a href="{{ route('catalog.index', ['sort' => 'newest']) }}" class="rounded-full px-3.5 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 hover:text-brand-900">Nouveautés</a>
        </nav>

        <div class="hidden flex-1 md:block">
            @include('partials.search-box', ['id' => 'search-desktop'])
        </div>

        <div class="ml-auto flex items-center gap-0.5 md:ml-0">
            <button type="button" class="btn-icon md:hidden" @click="mobileSearch = !mobileSearch" aria-label="Rechercher" :aria-expanded="mobileSearch.toString()">
                <x-icon name="search" />
            </button>

            @auth
                <a href="{{ route('account.wishlist') }}" class="btn-icon hidden sm:inline-flex" aria-label="Mes favoris"><x-icon name="heart" /></a>
                <div x-data="{ open: false }" class="relative" @click.outside="open = false" @keydown.escape="open = false">
                    <button type="button" class="btn-icon" @click="open = !open" :aria-expanded="open.toString()" aria-haspopup="true" aria-label="Mon compte">
                        <span class="grid size-8 place-items-center rounded-full bg-brand-900 text-xs font-bold text-white">{{ auth()->user()->initials() }}</span>
                    </button>
                    <div x-show="open" x-cloak x-transition.origin.top.right class="absolute right-0 mt-2 w-60 rounded-2xl bg-white p-2 shadow-[var(--shadow-lift)] ring-1 ring-zinc-900/5">
                        <div class="px-3 py-2">
                            <p class="truncate text-sm font-semibold text-brand-900">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-zinc-500">{{ auth()->user()->email }}</p>
                        </div>
                        <div class="my-1 divider"></div>
                        @can('admin.access')
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-accent-700 hover:bg-accent-50"><x-icon name="chart" class="size-4" /> Administration</a>
                        @endcan
                        <a href="{{ route('account.dashboard') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50"><x-icon name="home" class="size-4" /> Tableau de bord</a>
                        <a href="{{ route('account.orders.index') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50"><x-icon name="box" class="size-4" /> Mes commandes</a>
                        <a href="{{ route('account.profile.edit') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50"><x-icon name="user" class="size-4" /> Mon profil</a>
                        <div class="my-1 divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50"><x-icon name="logout" class="size-4" /> Déconnexion</button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="btn-icon sm:hidden" aria-label="Se connecter"><x-icon name="user" /></a>
                <a href="{{ route('login') }}" class="btn btn-ghost hidden sm:inline-flex">Connexion</a>
            @endauth

            <button type="button" class="btn-icon relative" @click="$store.cart.show()" aria-label="Ouvrir le panier">
                <x-icon name="bag" />
                <span x-cloak x-show="$store.cart.count > 0" x-text="$store.cart.count > 99 ? '99+' : $store.cart.count"
                      class="absolute -top-0.5 -right-0.5 grid h-5 min-w-5 place-items-center rounded-full bg-accent-700 px-1 text-[11px] font-bold text-white ring-2 ring-white"></span>
            </button>
        </div>
    </div>

    <div x-show="mobileSearch" x-collapse x-cloak class="border-t border-zinc-100 px-4 py-3 md:hidden">
        @include('partials.search-box', ['id' => 'search-mobile'])
    </div>

    {{-- Mobile navigation drawer --}}
    <div x-show="mobileMenu" x-cloak class="fixed inset-0 z-[70] lg:hidden" role="dialog" aria-modal="true" aria-label="Menu" @keydown.escape.window="mobileMenu = false">
        <div x-show="mobileMenu" x-transition.opacity class="absolute inset-0 bg-brand-950/40" @click="mobileMenu = false"></div>
        <nav x-show="mobileMenu" x-trap.noscroll="mobileMenu"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
             class="absolute inset-y-0 left-0 flex w-[86%] max-w-sm flex-col bg-white shadow-2xl">
            <div class="flex h-16 items-center justify-between border-b border-zinc-100 px-4">
                <x-logo />
                <button type="button" class="btn-icon" @click="mobileMenu = false" aria-label="Fermer le menu"><x-icon name="x" /></button>
            </div>
            <div class="flex-1 overflow-y-auto p-4">
                <a href="{{ route('catalog.index') }}" class="flex items-center justify-between rounded-xl px-3 py-3 text-base font-semibold text-brand-900 hover:bg-zinc-50">Toute la boutique <x-icon name="arrow-right" class="size-4" /></a>
                <a href="{{ route('catalog.index', ['on_sale' => 1]) }}" class="flex items-center justify-between rounded-xl px-3 py-3 text-base font-semibold text-accent-700 hover:bg-accent-50">Promotions <x-icon name="percent" class="size-4" /></a>
                <a href="{{ route('catalog.index', ['sort' => 'newest']) }}" class="flex items-center justify-between rounded-xl px-3 py-3 text-base font-semibold text-brand-900 hover:bg-zinc-50">Nouveautés <x-icon name="sparkles" class="size-4" /></a>
                <p class="mt-5 mb-2 px-3 text-xs font-bold tracking-widest text-zinc-500 uppercase">Catégories</p>
                @foreach ($navCategories as $category)
                    <div x-data="{ open: false }">
                        <div class="flex items-center">
                            <a href="{{ route('catalog.category', $category['slug']) }}" class="flex flex-1 items-center gap-3 rounded-xl px-3 py-2.5 text-[15px] font-medium text-zinc-800 hover:bg-zinc-50">
                                <span class="size-9 overflow-hidden rounded-lg bg-sand"><x-product-image :src="$category['image_url']" alt="" /></span>
                                {{ $category['name'] }}
                            </a>
                            @if (! empty($category['children']))
                                <button type="button" class="btn-icon" @click="open = !open" :aria-expanded="open.toString()" aria-label="Sous-catégories de {{ $category['name'] }}">
                                    <x-icon name="chevron-down" class="size-4 transition" x-bind:class="open && 'rotate-180'" />
                                </button>
                            @endif
                        </div>
                        @if (! empty($category['children']))
                            <div x-show="open" x-collapse x-cloak class="ml-12 border-l border-zinc-100 pl-3">
                                @foreach ($category['children'] as $child)
                                    <a href="{{ route('catalog.category', $child['slug']) }}" class="block rounded-lg px-3 py-2 text-sm text-zinc-600 hover:bg-zinc-50">{{ $child['name'] }}</a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="border-t border-zinc-100 p-4">
                @auth
                    <a href="{{ route('account.dashboard') }}" class="btn btn-secondary w-full">Mon compte</a>
                @else
                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('login') }}" class="btn btn-secondary">Connexion</a>
                        <a href="{{ route('register') }}" class="btn btn-primary">Inscription</a>
                    </div>
                @endauth
            </div>
        </nav>
    </div>
</header>
