<?php

namespace App\Enums;

enum RoleSlug: string
{
    case SuperAdmin = 'super-admin';
    case Admin = 'admin';
    case Manager = 'manager';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super administrateur',
            self::Admin => 'Administrateur',
            self::Manager => 'Gestionnaire',
            self::Customer => 'Client',
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::Customer;
    }
}
