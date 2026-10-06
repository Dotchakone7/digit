@extends('layouts.shop')

@section('title', $category?->meta_title ?: ($category?->name ?? ($q ? 'Résultats pour « '.$q.' »' : 'Toute la boutique')))
@section('meta_description', $category?->meta_description ?: ($category?->description ?: 'Découvrez notre catalogue : des produits sélectionnés avec livraison rapide.'))
@if ($noindex)
    @section('robots', 'noindex, follow')
@endif

@section('content')
    <livewire:shop.catalog :category="$category" />
@endsection
