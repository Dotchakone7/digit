<?php

namespace Database\Factories;

use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ShippingMethod> */
class ShippingMethodFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'ship-'.Str::lower(Str::random(6)),
            'name' => 'Livraison standard',
            'description' => 'Livraison à domicile',
            'price' => 2000,
            'estimated_delay' => '24 à 72 h',
            'is_active' => true,
        ];
    }
}
