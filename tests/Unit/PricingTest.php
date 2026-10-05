<?php

namespace Tests\Unit;

use App\Enums\CouponType;
use App\Enums\OrderStatus;
use App\Models\Coupon;
use App\Services\CouponService;
use App\Support\Money;
use Tests\TestCase;

class PricingTest extends TestCase
{
    public function test_percentage_discounts_are_capped(): void
    {
        $service = new CouponService;
        $coupon = new Coupon(['type' => CouponType::Percent, 'value' => 10, 'max_discount_amount' => 3000]);

        $this->assertSame(2000, $service->discountFor($coupon, 20000));
        $this->assertSame(3000, $service->discountFor($coupon, 90000));
    }

    public function test_fixed_discounts_never_exceed_the_subtotal(): void
    {
        $coupon = new Coupon(['type' => CouponType::Fixed, 'value' => 5000]);

        $this->assertSame(3000, (new CouponService)->discountFor($coupon, 3000));
    }

    public function test_money_is_formatted_and_parsed(): void
    {
        $this->assertSame("12\u{202F}500\u{00A0}FCFA", Money::format(12500));
        $this->assertSame(12500, Money::toMinor('12 500'));
        $this->assertNull(Money::toMinor(''));
    }

    public function test_order_workflow_rules(): void
    {
        $this->assertTrue(OrderStatus::Pending->canTransitionTo(OrderStatus::Confirmed));
        $this->assertFalse(OrderStatus::Pending->canTransitionTo(OrderStatus::Delivered));
        $this->assertFalse(OrderStatus::Delivered->canTransitionTo(OrderStatus::Cancelled));
        $this->assertSame([], OrderStatus::Cancelled->allowedTransitions());
    }
}
