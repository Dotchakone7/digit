<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name', 'description', 'price', 'free_over_amount', 'estimated_delay', 'is_active', 'position'])]
class ShippingMethod extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'free_over_amount' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('position')->orderBy('price');
    }

    public function costFor(int $subtotal): int
    {
        if ($this->free_over_amount !== null && $subtotal >= $this->free_over_amount) {
            return 0;
        }

        return $this->price;
    }
}
