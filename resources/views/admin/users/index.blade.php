@extends('layouts.admin')

@section('title', 'Utilisateurs')

@section('content')
    <x-admin.page-header title="Utilisateurs" :subtitle="$users->total().' compte(s)'" />
    <form method="GET" class="card mb-4 grid gap-3 p-4 sm:grid-cols-[1fr_200px_160px_auto]">
        <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nom, e-mail, téléphone…" class="input" aria-label="Rechercher">
        <x-form.select name="role" :options="collect(\App\Enums\RoleSlug::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])" :value="$filters['role'] ?? ''" placeholder="Tous les rôles" aria-label="Rôle" />
        <x-form.select name="active" :options="['1' => 'Actifs', '0' => 'Désactivés']" :value="$filters['active'] ?? ''" placeholder="Tous" aria-label="État" />
        <button class="btn btn-secondary">Filtrer</button>
    </form>
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th>Utilisateur</th><th>Rôle</th><th class="text-right">Commandes</th><th class="text-right">CA généré</th><th>Inscrit le</th><th>État</th></tr></thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="cursor-pointer" onclick="window.location='{{ route('admin.users.show', $user) }}'">
                            <td><div class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-full bg-zinc-100 text-xs font-bold text-zinc-700">{{ $user->initials() }}</span><div><a href="{{ route('admin.users.show', $user) }}" class="font-medium text-zinc-900 hover:underline">{{ $user->name }}</a><p class="text-xs text-zinc-500">{{ $user->email }}</p></div></div></td>
                            <td><span @class(['badge', 'badge-primary' => $user->isStaff(), 'badge-neutral' => ! $user->isStaff()])>{{ $user->roleSlug()->label() }}</span></td>
                            <td class="text-right tabular-nums">{{ $user->orders_count }}</td>
                            <td class="text-right tabular-nums">{{ money((int) $user->revenue) }}</td>
                            <td class="text-zinc-600">{{ $user->created_at->format('d/m/Y') }}</td>
                            <td><span @class(['badge', 'badge-success' => $user->is_active, 'badge-danger' => ! $user->is_active])>{{ $user->is_active ? 'Actif' : 'Désactivé' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state icon="users" title="Aucun utilisateur trouvé" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $users->links() }}</div>
@endsection
