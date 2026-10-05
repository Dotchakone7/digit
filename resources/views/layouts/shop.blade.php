<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-pt-24">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="cart-count" content="{{ $cartCount }}">
    <meta name="theme-color" content="#0a1d37">
    @include('partials.seo')
    <link rel="icon" href="{{ asset(config('shop.favicon')) }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="flex min-h-screen flex-col">
    <a href="#contenu" class="sr-only z-[100] rounded-full bg-brand-900 px-4 py-2 text-white focus:not-sr-only focus:fixed focus:top-3 focus:left-3">Aller au contenu</a>

    @include('partials.header')

    <main id="contenu" class="flex-1">
        @yield('content')
    </main>

    @include('partials.footer')

    <x-cart-drawer />
    <x-confirm-dialog />
    <x-toasts />
    @if (config('assistant.enabled'))
        @include('partials.assistant')
    @endif
    @stack('scripts')
</body>
</html>
