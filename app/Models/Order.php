<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * status / payment_status are not fillable: they only change through
 * OrderStatusService and PaymentService, which record the history.
 */
#[Fillable([
    'number', 'user_id', 'customer_name', 'customer_email', 'customer_phone', 'shipping_address',
    'shipping_method_id', 'shipping_method_name', 'payment_method', 'coupon_id', 'coupon_code',
    'subtotal', 'discount_total', 'shipping_total', 'total', 'currency', 'notes',
])]
class Order extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'subtotal' => 'integer',
            'discount_total' => 'integer',
            'shipping_total' => 'integer',
            'total' => 'integer',
            'confirmed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class)->latest('id');
    }

    public function latestShipment(): HasOne
    {
        return $this->hasOne(Shipment::class)->latestOfMany();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at')->orderBy('id');
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class)->latest();
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function scopePaid(Builder $query): void
    {
        $query->where('payment_status', PaymentStatus::Paid);
    }

    /** Orders that count as revenue (paid and not refunded/cancelled). */
    public function scopeRevenue(Builder $query): void
    {
        $query->where('payment_status', PaymentStatus::Paid)
            ->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Refunded]);
    }

    public function itemsCount(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function isCancellableByCustomer(): bool
    {
        return in_array($this->status, [OrderStatus::Pending, OrderStatus::Confirmed], true)
            && $this->payment_status !== PaymentStatus::Paid;
    }

    public function canRequestReturn(): bool
    {
        $window = (int) config('shop.orders.return_window_days');

        return $this->status === OrderStatus::Delivered
            && $this->delivered_at?->gt(now()->subDays($window))
            && ! $this->returnRequests()->exists();
    }

    public function awaitsPayment(): bool
    {
        return $this->status === OrderStatus::Pending
            && in_array($this->payment_status, [PaymentStatus::Pending, PaymentStatus::Failed, PaymentStatus::Expired, PaymentStatus::Cancelled], true);
    }

    public function shippingAddressLine(): string
    {
        $address = $this->shipping_address ?? [];

        return collect([$address['street'] ?? null, $address['district'] ?? null, $address['city'] ?? null])
            ->filter()->implode(', ');
    }
}
