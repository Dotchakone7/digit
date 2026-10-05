<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'name', 'sku', 'options', 'price', 'stock', 'is_active', 'position'])]
class ProductVariant extends Model
{
    protected function casts(): array
    {
        return [
            'options' => 'array',
            'price' => 'integer',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** A variant without its own price inherits the product's current price. */
    public function currentPrice(): int
    {
        return $this->price ?? $this->product->currentPrice();
    }
}
