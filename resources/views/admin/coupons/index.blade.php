@extends('layouts.admin')

@section('title', 'Promotions')

@section('content')
    <x-admin.page-header title="Codes promo" subtitle="Réductions calculées et vérifiées côté serveur au moment de la commande.">
        <a wire:navigate href="{{ route('admin.coupons.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nouveau code</a>
    </x-admin.page-header>
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th>Code</th><th>Réduction</th><th>Conditions</th><th>Validité</th><th class="text-right">Utilisations</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                    @forelse ($coupons as $coupon)
                        @php($expired = $coupon->ends_at?->isPast())
                        <tr>
                            <td><span class="rounded-md bg-zinc-100 px-2 py-1 font-mono text-[13px] font-semibold">{{ $coupon->code }}</span><p class="mt-1 text-xs text-zinc-500">{{ $coupon->description }}</p></td>
                            <td class="font-semibold">{{ $coupon->displayValue() }}@if ($coupon->max_discount_amount)<span class="block text-xs font-normal text-zinc-500">plafond {{ money($coupon->max_discount_amount) }}</span>@endif</td>
                            <td class="text-xs text-zinc-600">{{ $coupon->min_order_amount ? 'Dès '.money($coupon->min_order_amount) : 'Sans minimum' }}@if ($coupon->usage_limit_per_user)<br>{{ $coupon->usage_limit_per_user }}× par client @endif</td>
                            <td class="text-xs text-zinc-600">{{ $coupon->starts_at?->format('d/m/Y') ?? '—' }} → {{ $coupon->ends_at?->format('d/m/Y') ?? 'illimité' }}</td>
                            <td class="text-right tabular-nums">{{ $coupon->used_count }}{{ $coupon->usage_limit ? ' / '.$coupon->usage_limit : '' }}</td>
                            <td><span @class(['badge', 'badge-success' => $coupon->is_active && ! $expired, 'badge-neutral' => ! $coupon->is_active, 'badge-danger' => $coupon->is_active && $expired])>{{ ! $coupon->is_active ? 'Inactif' : ($expired ? 'Expiré' : 'Actif') }}</span></td>
                            <td><div class="flex justify-end gap-1">
                                <a wire:navigate href="{{ route('admin.coupons.edit', $coupon) }}" class="btn-icon size-8" aria-label="Modifier"><x-icon name="edit" class="size-4" /></a>
                                <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" data-confirm="Un code déjà utilisé sera désactivé au lieu d’être supprimé." data-confirm-title="Supprimer {{ $coupon->code }} ?" data-confirm-label="Supprimer">@csrf @method('DELETE')<button class="btn-icon size-8 text-zinc-400 hover:text-danger-600" aria-label="Supprimer"><x-icon name="trash" class="size-4" /></button></form>
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty-state icon="percent" title="Aucun code promo"><a wire:navigate href="{{ route('admin.coupons.create') }}" class="btn btn-primary">Créer un code</a></x-empty-state></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $coupons->links() }}</div>
@endsection
