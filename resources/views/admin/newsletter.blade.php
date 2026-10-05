@extends('layouts.admin')

@section('title', 'Newsletter')

@section('content')
    <x-admin.page-header title="Newsletter" :subtitle="$active.' abonné(s) actif(s)'">
        <a href="{{ route('admin.newsletter.export') }}" class="btn btn-secondary"><x-icon name="download" class="size-4" /> Exporter (CSV)</a>
    </x-admin.page-header>
    <div class="card overflow-hidden">
        <div class="overflow-x-auto"><table class="table-admin">
            <thead><tr><th>E-mail</th><th>Inscrit le</th><th>État</th></tr></thead>
            <tbody>
                @forelse ($subscribers as $subscriber)
                    <tr><td>{{ $subscriber->email }}</td><td>{{ $subscriber->created_at->format('d/m/Y') }}</td><td><span @class(['badge', 'badge-success' => ! $subscriber->unsubscribed_at, 'badge-neutral' => $subscriber->unsubscribed_at])>{{ $subscriber->unsubscribed_at ? 'Désinscrit' : 'Abonné' }}</span></td></tr>
                @empty
                    <tr><td colspan="3"><x-empty-state icon="mail" title="Aucun abonné pour le moment" /></td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div>
    <div class="mt-6">{{ $subscribers->links() }}</div>
@endsection
