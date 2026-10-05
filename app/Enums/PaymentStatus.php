<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Processing => 'En cours de vérification',
            self::Paid => 'Payé',
            self::Failed => 'Échoué',
            self::Cancelled => 'Annulé',
            self::Expired => 'Expiré',
            self::Refunded => 'Remboursé',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Processing => 'info',
            self::Paid => 'success',
            self::Failed, self::Expired => 'danger',
            self::Cancelled, self::Refunded => 'neutral',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Paid, self::Failed, self::Cancelled, self::Expired, self::Refunded], true);
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Processing], true);
    }
}
