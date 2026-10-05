<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Production-safe reference data. Demo content is seeded separately:
     * php artisan db:seed --class=DemoSeeder (refused in production).
     */
    public function run(): void
    {
        $this->call([RoleSeeder::class, ShippingMethodSeeder::class]);

        if (app()->environment('local', 'staging', 'testing') && $this->command?->confirm('Charger les données de démonstration ?', true)) {
            $this->call(DemoSeeder::class);
        }
    }
}
