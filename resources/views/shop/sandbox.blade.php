<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
    <title>Sandbox de paiement</title>
    @vite(['resources/css/app.css'])
</head>
<body class="grid min-h-screen place-items-center bg-zinc-900 p-4">
    <div class="w-full max-w-sm rounded-3xl bg-white p-6 shadow-2xl">
        <p class="badge badge-warning">Environnement de test — aucun argent réel</p>
        <h1 class="mt-4 font-sans text-xl font-bold">Simulateur de prestataire</h1>
        <p class="mt-1 text-sm text-zinc-500">Cette page imite la page de paiement hébergée d’un prestataire. Le résultat est transmis par un webhook signé.</p>
        <dl class="mt-5 space-y-1 rounded-2xl bg-zinc-50 p-4 text-sm">
            <div class="flex justify-between"><dt class="text-zinc-500">Marchand</dt><dd class="font-semibold">{{ config('shop.name') }}</dd></div>
            <div class="flex justify-between"><dt class="text-zinc-500">Référence</dt><dd class="font-mono">{{ $payment->reference }}</dd></div>
            <div class="flex justify-between"><dt class="text-zinc-500">Montant</dt><dd class="font-bold">{{ money($payment->amount) }}</dd></div>
        </dl>
        <form method="POST" action="{{ route('payments.sandbox.complete', $payment) }}" class="mt-6 grid gap-2">
            @csrf
            <button name="outcome" value="paid" class="btn btn-success w-full">Simuler un paiement réussi</button>
            <button name="outcome" value="failed" class="btn btn-danger-soft w-full">Simuler un refus</button>
            <button name="outcome" value="cancelled" class="btn btn-ghost w-full">Annuler</button>
        </form>
    </div>
</body>
</html>
