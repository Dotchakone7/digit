<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case Pending = 'pending';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'À préparer',
            self::InTransit => 'En cours de livraison',
            self::Delivered => 'Livré',
            self::Failed => 'Échec de livraison',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::InTransit => 'primary',
            self::Delivered => 'success',
            self::Failed => 'danger',
        };
    }
}
