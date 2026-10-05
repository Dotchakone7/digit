@extends('layouts.account')

@section('title', 'Mes favoris')

@section('account')
    <h1 class="text-2xl font-extrabold sm:text-3xl">Mes favoris</h1>
    @if ($products->isEmpty())
        <div class="mt-6 card"><x-empty-state icon="heart" title="Aucun favori pour l’instant" text="Cliquez sur le cœur d’un produit pour le retrouver ici, sur tous vos appareils.">
            <a href="{{ route('catalog.index') }}" class="btn btn-primary">Explorer le catalogue</a>
        </x-empty-state></div>
    @else
        <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-5 xl:grid-cols-3">
            @foreach ($products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
        <div class="mt-8">{{ $products->links() }}</div>
    @endif
@endsection
