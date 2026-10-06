<div>
    <div class="card mb-4 grid gap-3 p-4 sm:grid-cols-[1fr_200px_160px]">
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-400" />
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Nom, e-mail, téléphone…" class="input pl-9" aria-label="Rechercher un utilisateur">
        </div>
        <x-form.select name="role" wire:model.live="role" :options="collect(\App\Enums\RoleSlug::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])" placeholder="Tous les rôles" aria-label="Rôle" />
        <x-form.select name="active" wire:model.live="active" :options="['1' => 'Actifs', '0' => 'Désactivés']" placeholder="Tous" aria-label="État" />
    </div>
    <div class="card overflow-hidden">
        <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="search,role,active,sortBy,gotoPage,nextPage,previousPage">
            <table class="table-admin">
                <thead>
                    <tr>
                        <x-admin.th-sort field="name" :sort-field="$sortField" :sort-direction="$sortDirection">Utilisateur</x-admin.th-sort>
                        <th>Rôle</th>
                        <x-admin.th-sort field="orders_count" :sort-field="$sortField" :sort-direction="$sortDirection" align="right">Commandes</x-admin.th-sort>
                        <x-admin.th-sort field="revenue" :sort-field="$sortField" :sort-direction="$sortDirection" align="right">CA généré</x-admin.th-sort>
                        <x-admin.th-sort field="created_at" :sort-field="$sortField" :sort-direction="$sortDirection">Inscrit le</x-admin.th-sort>
                        <th>État</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr wire:key="user-{{ $user->id }}" class="cursor-pointer" x-on:click="if (! $event.target.closest('a')) Livewire.navigate('{{ route('admin.users.show', $user) }}')">
                            <td><div class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-full bg-zinc-100 text-xs font-bold text-zinc-700">{{ $user->initials() }}</span><div><a wire:navigate href="{{ route('admin.users.show', $user) }}" class="font-medium text-zinc-900 hover:underline">{{ $user->name }}</a><p class="text-xs text-zinc-500">{{ $user->email }}</p></div></div></td>
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
</div>
