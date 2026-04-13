<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\DemandeMateriel;
use App\Entity\LigneDemande;
use App\Entity\User;
use App\Message\NotificationMessage;
use App\Repository\DemandeMaterielRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class DemandeService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly DemandeMaterielRepository $demandeRepository,
        private readonly MessageBusInterface $bus,
    ) {}

    /**
     * Crée et persiste une nouvelle demande de matériel.
     */
    public function creerDemande(User $user, DemandeMateriel $demande): DemandeMateriel
    {
        $demande->setRequester($user);
        $demande->setReference($this->genererReference());

        $this->em->persist($demande);
        $this->em->flush();

        $this->bus->dispatch(new NotificationMessage($demande->getId(), 'new_demande'));

        return $demande;
    }

    /**
     * Génère une référence unique au format DEM-YYYYMMDD-XXXX.
     * XXXX est un compteur incrémentiel basé sur le nombre de demandes du jour.
     */
    public function genererReference(): string
    {
        $today = new \DateTimeImmutable();
        $dateStr = $today->format('Ymd');

        $count = $this->demandeRepository->countTodayDemandes();
        $index = $count + 1;

        return sprintf('DEM-%s-%04d', $dateStr, $index);
    }
}
