<?php

namespace Tests;

use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function customer(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function product(array $attributes = []): Product
    {
        return Product::factory()->create($attributes + ['price' => 10000, 'stock' => 10]);
    }

    protected function shippingMethod(array $attributes = []): ShippingMethod
    {
        return ShippingMethod::factory()->create($attributes + ['price' => 2000]);
    }

    /** Valid checkout form for the given customer. */
    protected function checkoutPayload(ShippingMethod $method, array $overrides = []): array
    {
        return array_replace_recursive([
            'customer_name' => 'Aya Traoré',
            'customer_email' => 'aya@example.com',
            'customer_phone' => '+225 07 00 00 00 00',
            'address' => ['full_name' => 'Aya Traoré', 'phone' => '+225 07 00 00 00 00', 'city' => 'Abidjan', 'district' => 'Cocody', 'street' => 'Rue 12'],
            'shipping_method_id' => $method->id,
            'payment_method' => 'cash_on_delivery',
        ], $overrides);
    }

    protected function addToCart(Product $product, int $quantity = 1, ?int $variantId = null)
    {
        return $this->postJson(route('cart.items.store'), ['product_id' => $product->id, 'variant_id' => $variantId, 'quantity' => $quantity]);
    }
}
