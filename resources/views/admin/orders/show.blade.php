@extends('layouts.admin')

@section('title', 'Commande '.$order->number)

@section('content')
    <livewire:admin.order-manager :order="$order" />
@endsection
