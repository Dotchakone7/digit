<?php

namespace App\Services;

use App\Enums\CouponType;
use App\Exceptions\BusinessException;
use App\Models\Coupon;
use App\Models\User;

/** All discount computations happen server-side, here. */
class CouponService
{
    public function findValid(string $code, int $subtotal, ?User $user): Coupon
    {
        $coupon = Coupon::query()->where('code', mb_strtoupper(trim($code)))->first();

        if ($coupon === null) {
            throw new BusinessException("Ce code promo n'existe pas.");
        }

        $this->assertUsable($coupon, $subtotal, $user);

        return $coupon;
    }

    public function assertUsable(Coupon $coupon, int $subtotal, ?User $user): void
    {
        if (! $coupon->is_active) {
            throw new BusinessException("Ce code promo n'est plus actif.");
        }

        if ($coupon->starts_at?->isFuture()) {
            throw new BusinessException("Ce code promo n'est pas encore valable.");
        }

        if ($coupon->ends_at?->isPast()) {
            throw new BusinessException('Ce code promo a expiré.');
        }

        if ($coupon->min_order_amount !== null && $subtotal < $coupon->min_order_amount) {
            throw new BusinessException('Ce code est valable dès '.money($coupon->min_order_amount).' d’achat.');
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            throw new BusinessException("Ce code promo a atteint sa limite d'utilisation.");
        }

        if ($user && $coupon->usage_limit_per_user !== null
            && $coupon->usages()->where('user_id', $user->id)->count() >= $coupon->usage_limit_per_user) {
            throw new BusinessException('Vous avez déjà utilisé ce code promo.');
        }
    }

    public function isUsable(Coupon $coupon, int $subtotal, ?User $user): bool
    {
        try {
            $this->assertUsable($coupon, $subtotal, $user);

            return true;
        } catch (BusinessException) {
            return false;
        }
    }

    public function discountFor(Coupon $coupon, int $subtotal): int
    {
        $discount = match ($coupon->type) {
            CouponType::Percent => intdiv($subtotal * min($coupon->value, 100), 100),
            CouponType::Fixed => $coupon->value,
        };

        if ($coupon->max_discount_amount !== null) {
            $discount = min($discount, $coupon->max_discount_amount);
        }

        return max(0, min($discount, $subtotal));
    }
}
