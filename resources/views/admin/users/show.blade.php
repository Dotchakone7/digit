@extends('layouts.admin')

@section('title', $user->name)

@section('content')
    <x-admin.page-header :title="$user->name" :subtitle="$user->email.' · '.($user->phone ?? 'pas de téléphone')" :back="route('admin.users.index')">
        <span @class(['badge px-3 py-1 text-sm', 'badge-success' => $user->is_active, 'badge-danger' => ! $user->is_active])>{{ $user->is_active ? 'Compte actif' : 'Compte désactivé' }}</span>
    </x-admin.page-header>

    <div class="grid gap-6 xl:grid-cols-[1fr_340px]">
        <div class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-3">
                <x-admin.stat-card label="Commandes" :value="$stats['orders']" icon="box" />
                <x-admin.stat-card label="Chiffre d’affaires" :value="money($stats['revenue'])" icon="wallet" />
                <x-admin.stat-card label="Avis publiés" :value="$stats['reviews']" icon="star" />
            </div>
            <section class="card overflow-hidden">
                <h2 class="px-5 py-4 text-base font-semibold">Dernières commandes</h2>
                <table class="table-admin">
                    <thead><tr><th>Commande</th><th>Date</th><th>Statut</th><th class="text-right">Total</th></tr></thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr><td><a href="{{ route('admin.orders.show', $order) }}" class="font-mono text-xs font-semibold text-brand-800 hover:underline">{{ $order->number }}</a></td><td>{{ $order->created_at->format('d/m/Y') }}</td><td><x-status-badge :status="$order->status" /></td><td class="text-right tabular-nums">{{ money($order->total) }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="py-6 text-center text-zinc-500">Aucune commande.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
            <section class="card p-5">
                <h2 class="text-base font-semibold">Adresses</h2>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    @forelse ($user->addresses as $address)
                        <div class="rounded-xl bg-zinc-50 p-3 text-sm text-zinc-700"><p class="font-medium">{{ $address->label }} @if ($address->is_default)<span class="badge badge-neutral">défaut</span>@endif</p>{{ $address->full_name }} · {{ $address->phone }}<br>{{ $address->oneLine() }}</div>
                    @empty
                        <p class="text-sm text-zinc-500">Aucune adresse enregistrée.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="card p-5 text-sm">
                <dl class="space-y-2">
                    <div class="flex justify-between"><dt class="text-zinc-500">Rôle</dt><dd class="font-medium">{{ $user->roleSlug()->label() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">Inscrit le</dt><dd>{{ $user->created_at->format('d/m/Y') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">Dernière connexion</dt><dd>{{ $user->last_login_at?->diffForHumans() ?? '—' }}</dd></div>
                </dl>
            </section>
            @can('update', $user)
                <section class="card space-y-4 p-5">
                    <h2 class="text-base font-semibold">Rôle & accès</h2>
                    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-3" data-confirm="Les droits de cet utilisateur vont changer immédiatement." data-confirm-title="Modifier le rôle ?" data-confirm-label="Modifier" data-confirm-tone="neutral">
                        @csrf @method('PATCH')
                        <x-form.select name="role" label="Rôle" :options="$roles" :value="$user->roleSlug()->value" />
                        <button class="btn btn-secondary w-full">Mettre à jour le rôle</button>
                    </form>
                    <form method="POST" action="{{ route('admin.users.update', $user) }}" data-confirm="{{ $user->is_active ? 'L’utilisateur sera immédiatement déconnecté et ne pourra plus se connecter.' : 'L’utilisateur pourra à nouveau se connecter.' }}" data-confirm-title="{{ $user->is_active ? 'Désactiver ce compte ?' : 'Réactiver ce compte ?' }}" data-confirm-label="{{ $user->is_active ? 'Désactiver' : 'Réactiver' }}" @if (! $user->is_active) data-confirm-tone="neutral" @endif>
                        @csrf @method('PATCH')
                        <input type="hidden" name="is_active" value="{{ $user->is_active ? 0 : 1 }}">
                        <button class="btn w-full {{ $user->is_active ? 'btn-danger-soft' : 'btn-success' }}">{{ $user->is_active ? 'Désactiver le compte' : 'Réactiver le compte' }}</button>
                    </form>
                    @can('delete', $user)
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm="Ce compte sans commande sera supprimé." data-confirm-title="Supprimer {{ $user->name }} ?" data-confirm-label="Supprimer">
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost w-full text-danger-700">Supprimer le compte</button>
                        </form>
                    @else
                        <p class="text-xs text-zinc-500">Un compte avec des commandes ne peut pas être supprimé (historique comptable) : désactivez-le.</p>
                    @endcan
                </section>
            @else
                <p class="rounded-xl bg-zinc-100 p-4 text-sm text-zinc-600">Vous ne pouvez pas modifier ce compte.</p>
            @endcan
        </aside>
    </div>
@endsection
