<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Published => 'Publié',
            self::Archived => 'Désactivé',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Published => 'success',
            self::Archived => 'danger',
        };
    }
}
