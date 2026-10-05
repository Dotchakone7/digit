<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** status is not fillable: it only changes through PaymentService. */
#[Fillable(['order_id', 'gateway', 'reference', 'provider_reference', 'amount', 'currency', 'meta', 'expires_at'])]
class Payment extends Model
{
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'integer',
            'meta' => 'array',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Processing]);
    }

    public function gatewayLabel(): string
    {
        return config("payments.gateways.{$this->gateway}.label", $this->gateway);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
