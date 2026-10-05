<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Confirmed => 'Confirmée',
            self::Processing => 'En préparation',
            self::Shipped => 'Expédiée',
            self::Delivered => 'Livrée',
            self::Cancelled => 'Annulée',
            self::Refunded => 'Remboursée',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed, self::Processing => 'info',
            self::Shipped => 'primary',
            self::Delivered => 'success',
            self::Cancelled => 'danger',
            self::Refunded => 'neutral',
        };
    }

    /**
     * Business workflow: which statuses an order may move to from this one.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Processing, self::Cancelled],
            self::Processing => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered],
            self::Delivered => [self::Refunded],
            self::Cancelled, self::Refunded => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /** Statuses whose stock must be given back when the order leaves them for "cancelled". */
    public function holdsStock(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed, self::Processing], true);
    }

    /** Main steps displayed in the order timeline. */
    public static function timeline(): array
    {
        return [self::Pending, self::Confirmed, self::Processing, self::Shipped, self::Delivered];
    }
}
