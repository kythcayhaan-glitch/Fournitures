<?php

declare(strict_types=1);

namespace App\Enum;

enum StatutDemande: string
{
    case PENDING   = 'pending';
    case REJECTED  = 'rejected';
    case DELIVERED = 'delivered';

    public function label(): string
    {
        return match($this) {
            self::PENDING   => 'En attente',
            self::REJECTED  => 'Rejetée',
            self::DELIVERED => 'Livrée',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::PENDING   => 'bg-warning text-dark',
            self::REJECTED  => 'bg-danger',
            self::DELIVERED => 'bg-success',
        };
    }
}
