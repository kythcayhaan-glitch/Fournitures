<?php

declare(strict_types=1);

namespace App\Enum;

enum TypeMouvement: string
{
    case ENTREE      = 'entree';
    case SORTIE      = 'sortie';
    case AJUSTEMENT  = 'ajustement';

    public function label(): string
    {
        return match($this) {
            self::ENTREE     => 'Entrée',
            self::SORTIE     => 'Sortie',
            self::AJUSTEMENT => 'Ajustement',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::ENTREE     => 'bg-success',
            self::SORTIE     => 'bg-danger',
            self::AJUSTEMENT => 'bg-secondary',
        };
    }
}
