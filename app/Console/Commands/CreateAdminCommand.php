<?php

namespace App\Console\Commands;

use App\Enums\RoleSlug;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

#[Signature('shop:create-admin {--role=super-admin : super-admin, admin ou manager}')]
#[Description('Crée (ou promeut) un compte d’administration de manière interactive')]
class CreateAdminCommand extends Command
{
    public function handle(): int
    {
        $role = RoleSlug::tryFrom((string) $this->option('role'));

        if ($role === null || ! $role->isStaff()) {
            $this->error('Rôle invalide : super-admin, admin ou manager.');

            return self::FAILURE;
        }

        $this->call('db:seed', ['--class' => 'RoleSeeder', '--force' => true]);

        $email = mb_strtolower(trim((string) $this->ask('Adresse e-mail')));
        $user = User::query()->firstWhere('email', $email);

        $data = [
            'email' => $email,
            'name' => $user?->name ?? $this->ask('Nom complet'),
            'password' => $this->secret('Mot de passe (12 caractères minimum)'),
        ];

        $validator = Validator::make($data, [
            'email' => ['required', 'email'],
            'name' => ['required', 'string', 'max:120'],
            'password' => ['required', Password::min(12)->letters()->mixedCase()->numbers()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user ??= new User;
        $user->forceFill([
            'name' => $data['name'],
            'email' => $email,
            'password' => $data['password'],
            'role_id' => Role::idFor($role),
            'is_active' => true,
            'email_verified_at' => now(),
        ])->save();

        $this->info("Compte {$email} prêt avec le rôle « {$role->label()} ». Connexion : ".route('login'));

        return self::SUCCESS;
    }
}
