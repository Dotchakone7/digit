<?php

namespace App\Models;

use App\Enums\CouponType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code', 'description', 'type', 'value', 'min_order_amount', 'max_discount_amount', 'usage_limit',
    'usage_limit_per_user', 'starts_at', 'ends_at', 'is_active',
])]
class Coupon extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'integer',
            'min_order_amount' => 'integer',
            'max_discount_amount' => 'integer',
            'usage_limit' => 'integer',
            'usage_limit_per_user' => 'integer',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected function code(): Attribute
    {
        return Attribute::set(fn (string $value) => mb_strtoupper(trim($value)));
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function displayValue(): string
    {
        return $this->type === CouponType::Percent ? $this->value.' %' : money($this->value);
    }
}
