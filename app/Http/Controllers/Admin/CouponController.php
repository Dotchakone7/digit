<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CouponType;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(): View
    {
        return view('admin.coupons.index', ['coupons' => Coupon::query()->latest()->paginate(20)]);
    }

    public function create(): View
    {
        return view('admin.coupons.form', ['coupon' => new Coupon(['type' => CouponType::Percent, 'is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Coupon::query()->create($this->validated($request));

        return redirect()->route('admin.coupons.index')->with('toast', ['type' => 'success', 'message' => 'Code promo créé.']);
    }

    public function edit(Coupon $coupon): View
    {
        return view('admin.coupons.form', ['coupon' => $coupon]);
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update($this->validated($request, $coupon));

        return redirect()->route('admin.coupons.index')->with('toast', ['type' => 'success', 'message' => 'Code promo enregistré.']);
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        if ($coupon->usages()->exists()) {
            $coupon->update(['is_active' => false]);

            return back()->with('toast', ['type' => 'success', 'message' => 'Ce code a déjà été utilisé : il a été désactivé plutôt que supprimé.']);
        }

        $coupon->delete();

        return back()->with('toast', ['type' => 'success', 'message' => 'Code promo supprimé.']);
    }

    private function validated(Request $request, ?Coupon $coupon = null): array
    {
        $isPercent = $request->input('type') === CouponType::Percent->value;
        $request->merge([
            'code' => mb_strtoupper(trim((string) $request->input('code'))),
            // Percentages are plain numbers, fixed amounts are money.
            'value' => $isPercent ? $request->input('value') : Money::toMinor($request->input('value')),
            'min_order_amount' => Money::toMinor($request->input('min_order_amount')),
            'max_discount_amount' => Money::toMinor($request->input('max_discount_amount')),
        ]);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9\-_]+$/', Rule::unique('coupons', 'code')->ignore($coupon?->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::enum(CouponType::class)],
            'value' => ['required', 'integer', 'min:1', $isPercent ? 'max:100' : 'max:999999999'],
            'min_order_amount' => ['nullable', 'integer', 'min:0'],
            'max_discount_amount' => ['nullable', 'integer', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ], ['code.regex' => 'Le code ne peut contenir que des lettres, chiffres, tirets et underscores.'],
            ['min_order_amount' => 'minimum de commande', 'max_discount_amount' => 'plafond de réduction', 'usage_limit' => 'limite d’utilisation', 'usage_limit_per_user' => 'limite par client']);

        return $data + ['is_active' => $request->boolean('is_active')];
    }
}
