<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['cart_id', 'product_id', 'product_variant_id', 'quantity'])]
class CartItem extends Model
{
    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function unitPrice(): int
    {
        return $this->variant?->currentPrice() ?? $this->product->currentPrice();
    }

    public function lineTotal(): int
    {
        return $this->unitPrice() * $this->quantity;
    }

    public function availableStock(): int
    {
        return $this->variant?->stock ?? $this->product->stock;
    }

    /** Whether the line can still be ordered as-is. */
    public function isPurchasable(): bool
    {
        return $this->product->isPublished()
            && ($this->variant === null || $this->variant->is_active)
            && $this->quantity <= $this->availableStock();
    }
}
