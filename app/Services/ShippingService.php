<?php

namespace App\Services;

use App\Models\ShippingMethod;
use Illuminate\Support\Collection;

class ShippingService
{
    /** @return Collection<int, ShippingMethod> */
    public function methods(): Collection
    {
        return once(fn () => ShippingMethod::query()->active()->get());
    }

    public function find(?int $id): ?ShippingMethod
    {
        return $id ? $this->methods()->firstWhere('id', $id) : null;
    }

    public function default(): ?ShippingMethod
    {
        return $this->methods()->first();
    }

    /** @return Collection<int, array{method: ShippingMethod, cost: int}> */
    public function quotes(int $subtotal): Collection
    {
        return $this->methods()->map(fn (ShippingMethod $method) => [
            'method' => $method,
            'cost' => $method->costFor($subtotal),
        ]);
    }
}
