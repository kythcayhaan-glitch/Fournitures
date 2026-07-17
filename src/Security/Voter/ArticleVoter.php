<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Article;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour les permissions sur les articles.
 */
class ArticleVoter extends Voter
{
    public const VIEW   = 'ARTICLE_VIEW';
    public const CREATE = 'ARTICLE_CREATE';
    public const EDIT   = 'ARTICLE_EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::CREATE, self::EDIT], true)) {
            return false;
        }

        // VIEW et CREATE n'ont pas besoin d'une entité
        if (in_array($attribute, [self::VIEW, self::CREATE], true)) {
            return true;
        }

        return $subject instanceof Article;
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
            self::CREATE, self::EDIT => $this->isAdminOrManager($user),
            default      => false,
        };
    }

    private function isAdminOrManager(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }
}
