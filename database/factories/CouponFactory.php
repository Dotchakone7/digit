<?php

namespace Database\Factories;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Coupon> */
class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'PROMO'.Str::upper(Str::random(5)),
            'type' => CouponType::Percent,
            'value' => 10,
            'is_active' => true,
        ];
    }

    public function fixed(int $amount): static
    {
        return $this->state(fn () => ['type' => CouponType::Fixed, 'value' => $amount]);
    }
}
