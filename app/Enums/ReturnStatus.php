<?php

namespace App\Enums;

enum ReturnStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Received = 'received';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Demandé',
            self::Approved => 'Accepté',
            self::Rejected => 'Refusé',
            self::Received => 'Produit reçu',
            self::Refunded => 'Remboursé',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Requested => 'warning',
            self::Approved, self::Received => 'info',
            self::Rejected => 'danger',
            self::Refunded => 'success',
        };
    }

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Requested => [self::Approved, self::Rejected],
            self::Approved => [self::Received, self::Rejected],
            self::Received => [self::Refunded],
            self::Rejected, self::Refunded => [],
        };
    }

    public static function reasons(): array
    {
        return [
            'defective' => 'Produit défectueux',
            'wrong_item' => 'Mauvais article reçu',
            'not_as_described' => 'Non conforme à la description',
            'damaged' => 'Endommagé à la livraison',
            'changed_mind' => "Je ne souhaite plus l'article",
            'other' => 'Autre',
        ];
    }
}
