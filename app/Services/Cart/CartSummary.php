<?php

namespace App\Services\Cart;

use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\ShippingMethod;
use Illuminate\Support\Collection;

final readonly class CartSummary
{
    /** @param Collection<int, CartItem> $items */
    public function __construct(
        public Collection $items,
        public int $subtotal,
        public int $discount,
        public ?int $shipping,
        public ?Coupon $coupon,
        public ?ShippingMethod $shippingMethod,
    ) {}

    public function total(): int
    {
        return max(0, $this->subtotal - $this->discount + ($this->shipping ?? 0));
    }

    public function count(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    /** Lines that cannot be ordered as-is (stock changed, product unpublished…). */
    public function unavailableItems(): Collection
    {
        return $this->items->reject(fn (CartItem $item) => $item->isPurchasable());
    }

    public function isCheckoutReady(): bool
    {
        return ! $this->isEmpty() && $this->unavailableItems()->isEmpty();
    }

    public function toArray(): array
    {
        return [
            'count' => $this->count(),
            'subtotal' => $this->subtotal,
            'subtotal_formatted' => money($this->subtotal),
            'discount' => $this->discount,
            'discount_formatted' => money($this->discount),
            'shipping' => $this->shipping,
            'shipping_formatted' => $this->shipping === null ? null : ($this->shipping === 0 ? 'Offerte' : money($this->shipping)),
            'total' => $this->total(),
            'total_formatted' => money($this->total()),
            'coupon' => $this->coupon?->code,
            'items' => $this->items->map(fn (CartItem $item) => [
                'id' => $item->id,
                'name' => $item->product->name,
                'variant' => $item->variant?->name,
                'url' => route('products.show', $item->product),
                'image' => $item->product->image_url,
                'quantity' => $item->quantity,
                'max' => min($item->availableStock(), (int) config('shop.catalog.max_quantity_per_line')),
                'unit_price_formatted' => money($item->unitPrice()),
                'line_total_formatted' => money($item->lineTotal()),
                'available' => $item->isPurchasable(),
            ])->values(),
        ];
    }
}
