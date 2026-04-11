<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\DemandeMateriel;
use App\Entity\Fourniture;
use App\Entity\LigneDemande;
use App\Entity\MouvementStock;
use App\Entity\User;
use App\Enum\TypeMouvement;
use App\Repository\FournitureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class StockService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FournitureRepository $fournitureRepository,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Déduit le stock lors de la livraison d'une ligne de demande.
     * Utilise la quantité servie (ou demandée si non renseignée).
     */
    public function deduireStock(LigneDemande $ligne, User $operateur, DemandeMateriel $demande): void
    {
        $this->em->wrapInTransaction(function () use ($ligne, $operateur, $demande): void {
            $fourniture = $ligne->getFourniture();
            if ($fourniture === null) {
                return;
            }

            $qty = $ligne->getQuantiteServie() > 0
                ? $ligne->getQuantiteServie()
                : $ligne->getQuantiteDemandee();

            $avant = $fourniture->getStockQuantity();
            $apres = max(0, $avant - $qty);

            $fourniture->setStockQuantity($apres);

            $mouvement = new MouvementStock();
            $mouvement->setType(TypeMouvement::SORTIE);
            $mouvement->setQuantite($qty);
            $mouvement->setQuantiteAvant($avant);
            $mouvement->setQuantiteApres($apres);
            $mouvement->setMotif(sprintf(
                'Livraison demande %s — %s',
                $demande->getReference(),
                $fourniture->getName()
            ));
            $mouvement->setFourniture($fourniture);
            $mouvement->setOperateur($operateur);
            $mouvement->setDemande($demande);

            $this->em->persist($mouvement);

            $this->logger->info('Stock déduit', [
                'fourniture' => $fourniture->getReference(),
                'quantite'   => $qty,
                'avant'      => $avant,
                'apres'      => $apres,
                'demande'    => $demande->getReference(),
            ]);
        });
    }

    /**
     * Ajoute du stock (entrée de marchandise).
     */
    public function ajouterStock(Fourniture $fourniture, int $qty, string $motif, User $operateur): void
    {
        $this->em->wrapInTransaction(function () use ($fourniture, $qty, $motif, $operateur): void {
            $avant = $fourniture->getStockQuantity();
            $apres = $avant + $qty;

            $fourniture->setStockQuantity($apres);

            $mouvement = new MouvementStock();
            $mouvement->setType(TypeMouvement::ENTREE);
            $mouvement->setQuantite($qty);
            $mouvement->setQuantiteAvant($avant);
            $mouvement->setQuantiteApres($apres);
            $mouvement->setMotif($motif);
            $mouvement->setFourniture($fourniture);
            $mouvement->setOperateur($operateur);

            $this->em->persist($mouvement);
        });
    }

    /**
     * Ajuste le stock à une nouvelle valeur absolue.
     */
    public function ajustementStock(Fourniture $fourniture, int $newQty, string $motif, User $operateur): void
    {
        $this->em->wrapInTransaction(function () use ($fourniture, $newQty, $motif, $operateur): void {
            $avant = $fourniture->getStockQuantity();

            $fourniture->setStockQuantity($newQty);

            $mouvement = new MouvementStock();
            $mouvement->setType(TypeMouvement::AJUSTEMENT);
            $mouvement->setQuantite(abs($newQty - $avant));
            $mouvement->setQuantiteAvant($avant);
            $mouvement->setQuantiteApres($newQty);
            $mouvement->setMotif($motif);
            $mouvement->setFourniture($fourniture);
            $mouvement->setOperateur($operateur);

            $this->em->persist($mouvement);

            $this->logger->info('Ajustement stock', [
                'fourniture' => $fourniture->getReference(),
                'avant'      => $avant,
                'apres'      => $newQty,
                'motif'      => $motif,
            ]);
        });
    }

    /**
     * Retourne la liste des fournitures en stock bas.
     *
     * @return Fourniture[]
     */
    public function getFournituresStockBas(): array
    {
        return $this->fournitureRepository->findStockBas();
    }
}
