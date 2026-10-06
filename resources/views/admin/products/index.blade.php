@extends('layouts.admin')

@section('title', 'Produits')

@section('content')
    <x-admin.page-header title="Produits" subtitle="Recherche, tri et modifications instantanés. Le stock se modifie directement dans le tableau.">
        <a wire:navigate href="{{ route('admin.products.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nouveau produit</a>
    </x-admin.page-header>

    <livewire:admin.product-table />
@endsection
