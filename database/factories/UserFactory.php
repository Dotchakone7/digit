<?php

namespace Database\Factories;

use App\Enums\RoleSlug;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'role_id' => fn () => self::roleId(RoleSlug::Customer),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+225 07 '.fake()->numerify('## ## ## ##'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function role(RoleSlug $role): static
    {
        return $this->state(fn () => ['role_id' => self::roleId($role)]);
    }

    public function superAdmin(): static
    {
        return $this->role(RoleSlug::SuperAdmin);
    }

    public function admin(): static
    {
        return $this->role(RoleSlug::Admin);
    }

    public function manager(): static
    {
        return $this->role(RoleSlug::Manager);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public static function roleId(RoleSlug $slug): int
    {
        return Role::query()->firstOrCreate(['slug' => $slug->value], ['name' => $slug->label()])->id;
    }
}
