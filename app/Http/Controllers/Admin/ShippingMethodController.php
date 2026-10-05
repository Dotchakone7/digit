<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingMethod;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShippingMethodController extends Controller
{
    public function index(): View
    {
        return view('admin.shipping.index', ['methods' => ShippingMethod::query()->orderBy('position')->withCount('orders')->get()]);
    }

    public function create(): View
    {
        return view('admin.shipping.form', ['method' => new ShippingMethod(['is_active' => true, 'price' => 0])]);
    }

    public function store(Request $request): RedirectResponse
    {
        ShippingMethod::query()->create($this->validated($request));

        return redirect()->route('admin.shipping-methods.index')->with('toast', ['type' => 'success', 'message' => 'Mode de livraison créé.']);
    }

    public function edit(ShippingMethod $shippingMethod): View
    {
        return view('admin.shipping.form', ['method' => $shippingMethod]);
    }

    public function update(Request $request, ShippingMethod $shippingMethod): RedirectResponse
    {
        $shippingMethod->update($this->validated($request, $shippingMethod));

        return redirect()->route('admin.shipping-methods.index')->with('toast', ['type' => 'success', 'message' => 'Mode de livraison enregistré.']);
    }

    public function destroy(ShippingMethod $shippingMethod): RedirectResponse
    {
        if ($shippingMethod->orders()->exists()) {
            $shippingMethod->update(['is_active' => false]);

            return back()->with('toast', ['type' => 'success', 'message' => 'Mode utilisé par des commandes : il a été désactivé.']);
        }

        $shippingMethod->delete();

        return back()->with('toast', ['type' => 'success', 'message' => 'Mode de livraison supprimé.']);
    }

    private function validated(Request $request, ?ShippingMethod $method = null): array
    {
        $request->merge([
            'code' => Str::slug($request->input('code') ?: $request->input('name', '')),
            'price' => Money::toMinor($request->input('price')) ?? 0,
            'free_over_amount' => Money::toMinor($request->input('free_over_amount')),
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'alpha_dash', 'max:40', Rule::unique('shipping_methods', 'code')->ignore($method?->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0'],
            'free_over_amount' => ['nullable', 'integer', 'min:0'],
            'estimated_delay' => ['nullable', 'string', 'max:120'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [], ['free_over_amount' => 'seuil de gratuité', 'estimated_delay' => 'délai estimé']) + ['is_active' => $request->boolean('is_active'), 'position' => (int) $request->input('position')];
    }
}
