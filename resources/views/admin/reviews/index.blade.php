@extends('layouts.admin')

@section('title', 'Avis clients')

@section('content')
    <x-admin.page-header title="Avis clients" subtitle="Seuls les avis approuvés sont publiés sur la boutique." />
    <livewire:admin.review-moderation />
@endsection
