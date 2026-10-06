@extends('layouts.admin')

@section('title', 'Commandes')

@section('content')
    <x-admin.page-header title="Commandes" subtitle="Les nouvelles commandes apparaissent automatiquement." />
    <livewire:admin.order-table />
@endsection
