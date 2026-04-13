<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\DemandeMateriel;
use App\Entity\LigneDemande;
use App\Entity\MouvementStock;
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
     * Supprime une demande sans toucher au stock.
     * Les mouvements de stock liés sont détachés (demande → null).
     */
    public function supprimerDemande(DemandeMateriel $demande): void
    {
        $this->em->createQueryBuilder()
            ->update(MouvementStock::class, 'm')
            ->set('m.demande', 'NULL')
            ->where('m.demande = :demande')
            ->setParameter('demande', $demande)
            ->getQuery()
            ->execute();

        $this->em->remove($demande);
        $this->em->flush();
    }

    /**
     * Supprime toutes les demandes sans toucher au stock.
     */
    public function supprimerToutesDemandes(): int
    {
        $this->em->createQueryBuilder()
            ->update(MouvementStock::class, 'm')
            ->set('m.demande', 'NULL')
            ->where('m.demande IS NOT NULL')
            ->getQuery()
            ->execute();

        $demandes = $this->demandeRepository->findAll();
        $count = count($demandes);

        foreach ($demandes as $demande) {
            $this->em->remove($demande);
        }
        $this->em->flush();

        return $count;
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
