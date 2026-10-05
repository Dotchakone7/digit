@extends('layouts.shop')

@section('title', 'Paiement de la commande '.$order->number)
@section('robots', 'noindex, nofollow')

@section('content')
    <div class="container-shop max-w-3xl py-10 lg:py-14">
        <a href="{{ route('account.orders.show', $order) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-500 hover:text-brand-900"><x-icon name="arrow-left" class="size-4" /> Ma commande</a>
        <h1 class="mt-2 text-3xl font-extrabold">Paiement de votre commande</h1>
        <p class="mt-2 text-zinc-600">Commande <span class="font-mono font-semibold text-brand-900">{{ $order->number }}</span> · Montant à payer : <strong class="text-brand-900">{{ money($order->total) }}</strong></p>

        @if ($payment?->gateway === 'manual_mobile_money' && $payment->status === \App\Enums\PaymentStatus::Pending && $momo)
            <div class="mt-8 card card-body">
                <h2 class="text-lg font-bold">1. Effectuez le transfert Mobile Money</h2>
                <p class="mt-1 text-sm text-zinc-500">Envoyez exactement <strong class="text-brand-900">{{ money($order->total) }}</strong> à l’un des numéros ci-dessous, en indiquant <strong class="font-mono">{{ $order->number }}</strong> en motif si possible.</p>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @foreach ($momo->operators() as $operator)
                        <div class="flex items-center justify-between rounded-2xl bg-sand/70 p-4" x-data="{ copied: false }">
                            <div>
                                <p class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">{{ $operator['label'] }}</p>
                                <p class="mt-1 font-mono text-lg font-bold text-brand-900">{{ $operator['number'] }}</p>
                                @if ($momo->accountName())<p class="text-xs text-zinc-500">{{ $momo->accountName() }}</p>@endif
                            </div>
                            <button type="button" class="btn-icon bg-white" @click="navigator.clipboard?.writeText(@js(preg_replace('/\s/', '', $operator['number']))); copied = true; setTimeout(() => copied = false, 1500)" aria-label="Copier le numéro">
                                <x-icon name="copy" class="size-4" x-show="!copied" /><x-icon name="check" class="size-4 text-success-600" x-show="copied" x-cloak />
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>

            <form method="POST" action="{{ route('payments.transfer', $payment) }}" class="mt-6 card card-body space-y-4">
                @csrf
                <h2 class="text-lg font-bold">2. Indiquez la référence de votre transaction</h2>
                <p class="-mt-2 text-sm text-zinc-500">Vous la trouverez dans le SMS de confirmation de votre opérateur. Nous vérifions chaque paiement avant de préparer la commande.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.select name="operator" label="Opérateur utilisé" :options="collect($momo->operators())->map(fn ($o) => $o['label'])->all()" placeholder="Choisir…" required />
                    <x-form.input name="payer_phone" type="tel" label="Numéro ayant payé" :value="$order->customer_phone" required />
                    <x-form.input name="transaction_id" label="Référence de transaction" placeholder="Ex. : MP240512.1234.A12345" required class="sm:col-span-2" />
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-full" data-loading="Envoi…">J’ai payé, envoyer la référence</button>
            </form>
        @elseif ($payment?->status === \App\Enums\PaymentStatus::Processing)
            <div class="mt-8 card card-body flex items-start gap-4">
                <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-info-50 text-info-700"><x-icon name="clock" /></span>
                <div>
                    <h2 class="text-lg font-bold">Paiement en cours de vérification</h2>
                    <p class="mt-1 text-sm text-zinc-600">Nous avons bien reçu votre référence de transaction. Vous serez notifié(e) dès que le paiement sera confirmé par notre équipe.</p>
                </div>
            </div>
        @endif

        @if ($canRetry)
            <form method="POST" action="{{ route('payments.store', $order) }}" class="mt-6 card card-body" x-data="{ method: @js($payment?->gateway ?? $gateways->keys()->first()) }">
                @csrf
                <h2 class="text-lg font-bold">{{ $payment ? 'Changer de moyen de paiement' : 'Choisir un moyen de paiement' }}</h2>
                @if ($payment && in_array($payment->status, [\App\Enums\PaymentStatus::Failed, \App\Enums\PaymentStatus::Expired, \App\Enums\PaymentStatus::Cancelled]))
                    <p class="mt-2 flex items-center gap-2 rounded-xl bg-danger-50 p-3 text-sm text-danger-700"><x-icon name="alert" class="size-4" /> Le dernier paiement est « {{ $payment->status->label() }} ». Vous pouvez réessayer.</p>
                @endif
                <div class="mt-4 grid gap-3">
                    @foreach ($gateways as $code => $gateway)
                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl p-4 ring-1 transition" :class="method === '{{ $code }}' ? 'ring-2 ring-brand-900' : 'ring-zinc-200'">
                            <input type="radio" name="payment_method" value="{{ $code }}" x-model="method" class="accent-brand-900">
                            <span class="text-sm"><span class="block font-semibold text-brand-900">{{ $gateway->label() }}</span><span class="text-zinc-500">{{ $gateway->description() }}</span></span>
                        </label>
                    @endforeach
                </div>
                <button type="submit" class="btn btn-secondary mt-4" data-loading>Continuer avec ce moyen de paiement</button>
            </form>
        @endif
    </div>
@endsection
