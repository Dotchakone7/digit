<?php

namespace Database\Factories;

use App\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Address> */
class AddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'label' => 'Maison',
            'full_name' => fake()->name(),
            'phone' => '+225 07 '.fake()->numerify('## ## ## ##'),
            'city' => 'Abidjan',
            'district' => fake()->randomElement(['Cocody', 'Plateau', 'Marcory', 'Yopougon', 'Treichville', 'Koumassi']),
            'street' => fake()->streetAddress(),
            'landmark' => 'Près de la pharmacie',
            'is_default' => true,
        ];
    }
}
