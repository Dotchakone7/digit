<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') | {{ config('shop.name') }}</title>
    <link rel="icon" href="{{ asset(config('shop.favicon')) }}">
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen flex-col bg-canvas">
    <header class="container-shop flex h-20 items-center"><x-logo /></header>
    <main class="container-shop flex flex-1 items-center justify-center py-12">
        <div class="max-w-lg text-center">
            <p class="font-display text-[7rem] leading-none font-extrabold text-brand-100 sm:text-[9rem]" aria-hidden="true">@yield('code')</p>
            <h1 class="-mt-6 text-3xl font-extrabold sm:text-4xl">@yield('heading')</h1>
            <p class="mt-4 text-zinc-600">@yield('message')</p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                @yield('actions')
                <a href="{{ url('/') }}" class="btn btn-primary btn-lg">Retour à l’accueil</a>
            </div>
        </div>
    </main>
</body>
</html>
