@extends('layouts.admin')

@section('title', $category->exists ? $category->name : 'Nouvelle catégorie')

@section('content')
    <x-admin.page-header :title="$category->exists ? 'Modifier la catégorie' : 'Nouvelle catégorie'" :back="route('admin.categories.index')" />

    <form method="POST" enctype="multipart/form-data" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="grid max-w-5xl gap-6 lg:grid-cols-[1fr_300px]">
        @csrf @if ($category->exists) @method('PUT') @endif
        <div class="card space-y-4 p-5" x-data="slugger(@js(old('name', $category->name)), @js(old('slug', $category->slug)), {{ $category->exists ? 'true' : 'false' }})">
            <div><label for="name" class="label">Nom <span class="text-danger-600">*</span></label><input id="name" name="name" x-model="name" @input="sync()" required maxlength="120" class="input @error('name') input-error @enderror">@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
            <div><label for="slug" class="label">Slug (URL)</label><input id="slug" name="slug" x-model="slug" @input="locked = true" class="input font-mono">@error('slug')<p class="field-error">{{ $message }}</p>@enderror</div>
            <x-form.select name="parent_id" label="Catégorie parente" :options="$parents" :value="$category->parent_id" placeholder="Aucune (catégorie principale)" />
            <x-form.textarea name="description" label="Description" :value="$category->description" rows="3" maxlength="2000" />
            <x-form.input name="meta_title" label="Titre SEO" :value="$category->meta_title" maxlength="255" />
            <x-form.textarea name="meta_description" label="Meta description" :value="$category->meta_description" rows="2" maxlength="320" />
        </div>
        <div class="space-y-6">
            <div class="card space-y-4 p-5">
                <x-form.checkbox name="is_active" label="Visible sur la boutique" :checked="$category->is_active" />
                <x-form.input name="position" type="number" min="0" label="Ordre d’affichage" :value="$category->position" />
                <button class="btn btn-primary w-full" data-loading="Enregistrement…">Enregistrer</button>
            </div>
            <div class="card space-y-3 p-5">
                <p class="label">Image</p>
                @if ($category->image_url)
                    <img src="{{ $category->image_url }}" alt="" class="aspect-[4/3] w-full rounded-lg object-cover">
                    <x-form.checkbox name="remove_image" label="Retirer l’image" />
                @endif
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-zinc-600 file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-sm file:font-medium">
                @error('image')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </form>
@endsection
