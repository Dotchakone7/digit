@extends('layouts.admin')

@section('title', $coupon->exists ? $coupon->code : 'Nouveau code promo')

@section('content')
    <x-admin.page-header :title="$coupon->exists ? 'Modifier '.$coupon->code : 'Nouveau code promo'" :back="route('admin.coupons.index')" />
    @php($isFixed = old('type', $coupon->type?->value) === 'fixed')
    <form method="POST" action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}" class="card max-w-3xl space-y-5 p-6" x-data="{ type: @js(old('type', $coupon->type?->value ?? 'percent')) }">
        @csrf @if ($coupon->exists) @method('PUT') @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.input name="code" label="Code" :value="$coupon->code" required maxlength="40" class="[&_input]:font-mono [&_input]:uppercase" hint="Lettres, chiffres, tirets." />
            <x-form.input name="description" label="Description interne" :value="$coupon->description" maxlength="255" />
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="label" for="type">Type</label>
                <select id="type" name="type" x-model="type" class="input">@foreach (\App\Enums\CouponType::cases() as $t)<option value="{{ $t->value }}">{{ $t->label() }}</option>@endforeach</select>
            </div>
            <div>
                <label class="label" for="value">Valeur <span x-text="type === 'percent' ? '(%)' : '({{ config('shop.currency.symbol') }})'"></span> <span class="text-danger-600">*</span></label>
                <input id="value" name="value" type="number" min="1" required class="input @error('value') input-error @enderror" value="{{ old('value', $coupon->exists ? ($isFixed ? \App\Support\Money::toMajor($coupon->value) : $coupon->value) : '') }}">
                @error('value')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div x-show="type === 'percent'"><x-form.input name="max_discount_amount" type="number" min="0" label="Plafond de réduction" :value="\App\Support\Money::toMajor($coupon->max_discount_amount)" /></div>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <x-form.input name="min_order_amount" type="number" min="0" label="Minimum de commande" :value="\App\Support\Money::toMajor($coupon->min_order_amount)" />
            <x-form.input name="usage_limit" type="number" min="1" label="Utilisations max (total)" :value="$coupon->usage_limit" />
            <x-form.input name="usage_limit_per_user" type="number" min="1" label="Utilisations max par client" :value="$coupon->usage_limit_per_user" />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.input name="starts_at" type="datetime-local" label="Début" :value="$coupon->starts_at?->format('Y-m-d\TH:i')" />
            <x-form.input name="ends_at" type="datetime-local" label="Fin" :value="$coupon->ends_at?->format('Y-m-d\TH:i')" />
        </div>
        <x-form.checkbox name="is_active" label="Code actif" :checked="$coupon->is_active" />
        <div class="flex justify-end"><button class="btn btn-primary" data-loading="Enregistrement…">Enregistrer</button></div>
    </form>
@endsection
