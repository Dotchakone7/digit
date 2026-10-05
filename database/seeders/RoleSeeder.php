<?php

namespace Database\Seeders;

use App\Enums\RoleSlug;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $descriptions = [
            RoleSlug::SuperAdmin->value => 'Accès total, y compris la gestion des administrateurs.',
            RoleSlug::Admin->value => 'Gestion complète de la boutique.',
            RoleSlug::Manager->value => 'Catalogue, commandes, avis et retours.',
            RoleSlug::Customer->value => 'Client de la boutique.',
        ];

        foreach (RoleSlug::cases() as $role) {
            Role::query()->updateOrCreate(
                ['slug' => $role->value],
                ['name' => $role->label(), 'description' => $descriptions[$role->value]],
            );
        }
    }
}
