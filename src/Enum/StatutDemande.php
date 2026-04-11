<?php

declare(strict_types=1);

namespace App\Enum;

enum StatutDemande: string
{
    case PENDING   = 'pending';
    case APPROVED  = 'approved';
    case REJECTED  = 'rejected';
    case DELIVERED = 'delivered';

    public function label(): string
    {
        return match($this) {
            self::PENDING   => 'En attente',
            self::APPROVED  => 'Approuvée',
            self::REJECTED  => 'Rejetée',
            self::DELIVERED => 'Livrée',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::PENDING   => 'bg-warning text-dark',
            self::APPROVED  => 'bg-info text-dark',
            self::REJECTED  => 'bg-danger',
            self::DELIVERED => 'bg-success',
        };
    }
}
