@extends('shop.pages.layout')

@section('page')
    @if ($info = setting('delivery_info'))
        {!! nl2br(e($info)) !!}
    @else
        <p>Nous livrons vos commandes rapidement, avec un suivi à chaque étape depuis votre espace client.</p>
    @endif

    <h2>Nos options de livraison</h2>
    @php($methods = app(\App\Services\ShippingService::class)->methods())
    @if ($methods->isEmpty())
        <p>Les options de livraison sont présentées lors de la validation de votre commande.</p>
    @else
        <div class="not-prose mt-4 divide-y divide-zinc-100 overflow-hidden rounded-2xl ring-1 ring-zinc-200">
            @foreach ($methods as $method)
                <div class="flex flex-wrap items-center justify-between gap-3 p-4">
                    <div>
                        <p class="font-semibold text-brand-900">{{ $method->name }}</p>
                        <p class="text-sm text-zinc-500">{{ $method->description }} @if ($method->estimated_delay)· {{ $method->estimated_delay }}@endif</p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-brand-900">{{ $method->price ? money($method->price) : 'Gratuit' }}</p>
                        @if ($method->free_over_amount)<p class="text-xs text-success-700">Offerte dès {{ money($method->free_over_amount) }}</p>@endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <h2>Suivi de commande</h2>
    <p>Dès l’expédition, le statut de votre commande passe à « Expédiée » et vous recevez une notification. Vous pouvez suivre chaque étape depuis la rubrique « Mes commandes » de votre compte.</p>
    <h2>À la réception</h2>
    <p>Vérifiez l’état de votre colis en présence du livreur. En cas de produit endommagé, signalez-le immédiatement et contactez notre service client.</p>
@endsection
