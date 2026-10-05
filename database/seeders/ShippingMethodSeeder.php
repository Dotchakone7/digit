<?php

namespace Database\Seeders;

use App\Models\ShippingMethod;
use Illuminate\Database\Seeder;

/** Default delivery options — prices are editable in Admin > Livraisons. */
class ShippingMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['code' => 'standard', 'name' => 'Livraison standard', 'description' => 'À domicile ou au bureau', 'price' => 2000, 'free_over_amount' => 50000, 'estimated_delay' => '24 à 72 h', 'position' => 1],
            ['code' => 'express', 'name' => 'Livraison express', 'description' => 'Livraison prioritaire en ville', 'price' => 3500, 'free_over_amount' => null, 'estimated_delay' => 'Le jour même (avant 14 h)', 'position' => 2],
            ['code' => 'pickup', 'name' => 'Retrait en boutique', 'description' => 'Récupérez votre commande sur place', 'price' => 0, 'free_over_amount' => null, 'estimated_delay' => 'Dès confirmation', 'position' => 3],
            ['code' => 'interior', 'name' => 'Intérieur du pays', 'description' => 'Expédition par transporteur', 'price' => 5000, 'free_over_amount' => null, 'estimated_delay' => '3 à 5 jours', 'position' => 4],
        ];

        foreach ($methods as $method) {
            ShippingMethod::query()->firstOrCreate(['code' => $method['code']], $method + ['is_active' => true]);
        }
    }
}
