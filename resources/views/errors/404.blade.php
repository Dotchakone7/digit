@extends('errors.layout')
@section('title', 'Page introuvable')
@section('code', '404')
@section('heading', 'Page introuvable')
@section('message', 'La page que vous cherchez n’existe pas ou a été déplacée. Essayez la recherche ou parcourez notre catalogue.')
@section('actions')<a href="{{ url('/boutique') }}" class="btn btn-secondary btn-lg">Voir le catalogue</a>@endsection
