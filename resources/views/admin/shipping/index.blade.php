@extends('layouts.admin')

@section('title', 'Livraisons')

@section('content')
    <x-admin.page-header title="Modes de livraison" subtitle="Tarifs et délais proposés au moment de la commande.">
        <a href="{{ route('admin.settings.edit', ['tab' => 'delivery']) }}" class="btn btn-secondary"><x-icon name="settings" class="size-4" /> Livreur partenaire</a>
        <a href="{{ route('admin.shipping-methods.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nouveau mode</a>
    </x-admin.page-header>
    <div class="card overflow-hidden">
        <table class="table-admin">
            <thead><tr><th>Mode</th><th>Délai</th><th class="text-right">Tarif</th><th class="text-right">Gratuit dès</th><th class="text-right">Commandes</th><th>Statut</th><th></th></tr></thead>
            <tbody>
                @forelse ($methods as $method)
                    <tr>
                        <td><p class="font-medium text-zinc-900">{{ $method->name }}</p><p class="text-xs text-zinc-500">{{ $method->description }}</p></td>
                        <td class="text-zinc-600">{{ $method->estimated_delay ?? '—' }}</td>
                        <td class="text-right tabular-nums">{{ $method->price ? money($method->price) : 'Gratuit' }}</td>
                        <td class="text-right tabular-nums">{{ $method->free_over_amount ? money($method->free_over_amount) : '—' }}</td>
                        <td class="text-right tabular-nums">{{ $method->orders_count }}</td>
                        <td><span @class(['badge', 'badge-success' => $method->is_active, 'badge-neutral' => ! $method->is_active])>{{ $method->is_active ? 'Actif' : 'Inactif' }}</span></td>
                        <td><div class="flex justify-end gap-1">
                            <a href="{{ route('admin.shipping-methods.edit', $method) }}" class="btn-icon size-8" aria-label="Modifier"><x-icon name="edit" class="size-4" /></a>
                            <form method="POST" action="{{ route('admin.shipping-methods.destroy', $method) }}" data-confirm="Un mode déjà utilisé sera désactivé au lieu d’être supprimé." data-confirm-title="Supprimer {{ $method->name }} ?" data-confirm-label="Supprimer">@csrf @method('DELETE')<button class="btn-icon size-8 text-zinc-400 hover:text-danger-600" aria-label="Supprimer"><x-icon name="trash" class="size-4" /></button></form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state icon="truck" title="Aucun mode de livraison" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
