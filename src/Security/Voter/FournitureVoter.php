<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Fourniture;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour les permissions sur les fournitures.
 */
class FournitureVoter extends Voter
{
    public const VIEW   = 'FOURNITURE_VIEW';
    public const CREATE = 'FOURNITURE_CREATE';
    public const EDIT   = 'FOURNITURE_EDIT';
    public const DELETE = 'FOURNITURE_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::CREATE, self::EDIT, self::DELETE], true)) {
            return false;
        }

        // VIEW et CREATE n'ont pas besoin d'une entité
        if (in_array($attribute, [self::VIEW, self::CREATE], true)) {
            return true;
        }

        return $subject instanceof Fourniture;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (!$user->isActive()) {
            return false;
        }

        return match ($attribute) {
            self::VIEW   => true, // tout utilisateur authentifié et actif
            self::CREATE, self::EDIT, self::DELETE => $this->isAdminOrManager($user),
            default      => false,
        };
    }

    private function isAdminOrManager(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }
}
