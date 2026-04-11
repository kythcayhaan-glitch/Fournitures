<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\DemandeMateriel;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour les permissions sur les demandes de matériel.
 */
class DemandeVoter extends Voter
{
    public const VIEW    = 'DEMANDE_VIEW';
    public const CREATE  = 'DEMANDE_CREATE';
    public const APPROVE = 'DEMANDE_APPROVE';
    public const REJECT  = 'DEMANDE_REJECT';
    public const DELIVER = 'DEMANDE_DELIVER';
    public const DELETE  = 'DEMANDE_DELETE';
    public const CANCEL  = 'DEMANDE_CANCEL';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [
            self::VIEW, self::CREATE, self::APPROVE,
            self::REJECT, self::DELIVER, self::DELETE, self::CANCEL,
        ], true)) {
            return false;
        }

        // CREATE n'a pas besoin d'un objet spécifique
        if ($attribute === self::CREATE) {
            return true;
        }

        return $subject instanceof DemandeMateriel;
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

        /** @var DemandeMateriel|null $demande */
        $demande = $subject instanceof DemandeMateriel ? $subject : null;

        return match ($attribute) {
            self::CREATE  => true, // tout utilisateur authentifié et actif
            self::VIEW    => $this->canView($demande, $user),
            self::APPROVE, self::REJECT, self::DELIVER => $this->isManager($user),
            self::DELETE  => $this->canDelete($demande, $user),
            self::CANCEL  => $this->canCancel($demande, $user),
            default       => false,
        };
    }

    private function canView(?DemandeMateriel $demande, User $user): bool
    {
        if ($this->isManager($user)) {
            return true;
        }

        return $demande?->getRequester()?->getId() === $user->getId();
    }

    private function canDelete(?DemandeMateriel $demande, User $user): bool
    {
        if (!in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return false;
        }

        return $demande?->isPending() === true;
    }

    private function canCancel(?DemandeMateriel $demande, User $user): bool
    {
        if ($demande === null || !$demande->isPending()) {
            return false;
        }

        return $demande->getRequester()?->getId() === $user->getId();
    }

    private function isManager(User $user): bool
    {
        return in_array('ROLE_MANAGER', $user->getRoles(), true)
            || in_array('ROLE_ADMIN', $user->getRoles(), true);
    }
}
