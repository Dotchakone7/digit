@extends('layouts.shop')

@section('title', $page['title'])
@section('meta_description', $page['description'])

@section('content')
    <div class="border-b border-zinc-100 bg-white">
        <div class="container-shop py-10">
            <x-breadcrumbs :items="[$page['title'] => null]" />
            <h1 class="mt-4 text-3xl font-extrabold sm:text-4xl">{{ $page['title'] }}</h1>
            <p class="mt-2 text-zinc-500">{{ $page['description'] }}</p>
        </div>
    </div>
    <div class="container-shop grid gap-10 py-12 lg:grid-cols-[1fr_280px]">
        <article class="prose-shop card card-body sm:p-10">
            @yield('page')
        </article>
        <aside class="space-y-2">
            @foreach (\App\Http\Controllers\Shop\PageController::PAGES as $slug => $info)
                <a wire:navigate href="{{ route('pages.show', $slug) }}" @class(['block rounded-xl px-4 py-3 text-sm font-medium transition', 'bg-brand-900 text-white' => $page['slug'] === $slug, 'bg-white text-zinc-700 ring-1 ring-zinc-900/5 hover:ring-zinc-300' => $page['slug'] !== $slug])>{{ $info['title'] }}</a>
            @endforeach
            <a wire:navigate href="{{ route('contact') }}" class="block rounded-xl bg-white px-4 py-3 text-sm font-medium text-zinc-700 ring-1 ring-zinc-900/5 transition hover:ring-zinc-300">Nous contacter</a>
        </aside>
    </div>
@endsection
