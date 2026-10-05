@extends('layouts.admin')

@section('title', 'Catégories')

@section('content')
    <x-admin.page-header title="Catégories" subtitle="Organisez votre catalogue (deux niveaux).">
        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nouvelle catégorie</a>
    </x-admin.page-header>

    <div class="card overflow-hidden">
        <table class="table-admin">
            <thead><tr><th>Catégorie</th><th class="text-right">Produits</th><th class="text-right">Ordre</th><th>Statut</th><th class="text-right"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
                @forelse ($categories as $category)
                    @foreach (collect([$category])->merge($category->children) as $row)
                        <tr>
                            <td>
                                <div @class(['flex items-center gap-3', 'pl-10' => $row->parent_id])>
                                    @if ($row->parent_id)<span class="text-zinc-300">└</span>@endif
                                    <span class="size-10 shrink-0 overflow-hidden rounded-lg bg-zinc-100"><x-product-image :src="$row->image_url" alt="" /></span>
                                    <div><a href="{{ route('admin.categories.edit', $row) }}" class="font-medium text-zinc-900 hover:underline">{{ $row->name }}</a><p class="text-xs text-zinc-500">/categorie/{{ $row->slug }}</p></div>
                                </div>
                            </td>
                            <td class="text-right tabular-nums">{{ $row->products_count }}</td>
                            <td class="text-right tabular-nums text-zinc-500">{{ $row->position }}</td>
                            <td><span @class(['badge', 'badge-success' => $row->is_active, 'badge-neutral' => ! $row->is_active])>{{ $row->is_active ? 'Active' : 'Masquée' }}</span></td>
                            <td>
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('admin.categories.edit', $row) }}" class="btn-icon size-8" aria-label="Modifier {{ $row->name }}"><x-icon name="edit" class="size-4" /></a>
                                    <form method="POST" action="{{ route('admin.categories.destroy', $row) }}" data-confirm="Cette action est définitive." data-confirm-title="Supprimer « {{ $row->name }} » ?" data-confirm-label="Supprimer">
                                        @csrf @method('DELETE')
                                        <button class="btn-icon size-8 text-zinc-400 hover:text-danger-600" aria-label="Supprimer {{ $row->name }}"><x-icon name="trash" class="size-4" /></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="5"><x-empty-state icon="folder" title="Aucune catégorie" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
