@extends('layouts.admin')

@section('title', 'Utilisateurs')

@section('content')
    <x-admin.page-header title="Utilisateurs" subtitle="Clients et membres de l’équipe." />
    <livewire:admin.user-table />
@endsection
