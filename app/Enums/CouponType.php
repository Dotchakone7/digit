<?php

namespace App\Enums;

enum CouponType: string
{
    case Percent = 'percent';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Percent => 'Pourcentage',
            self::Fixed => 'Montant fixe',
        };
    }
}
