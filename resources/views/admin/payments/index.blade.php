@extends('layouts.admin')

@section('title', 'Paiements')

@section('content')
    <x-admin.page-header title="Paiements" subtitle="Un paiement n’est confirmé que par le prestataire (webhook vérifié) ou par vérification manuelle." />
    <livewire:admin.payment-table />
@endsection
