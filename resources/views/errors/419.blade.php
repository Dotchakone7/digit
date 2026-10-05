@extends('errors.layout')
@section('title', 'Session expirée')
@section('code', '419')
@section('heading', 'Session expirée')
@section('message', 'Votre session a expiré pour des raisons de sécurité. Rechargez la page et réessayez.')
@section('actions')<a href="{{ url()->previous() }}" class="btn btn-secondary btn-lg">Revenir en arrière</a>@endsection
