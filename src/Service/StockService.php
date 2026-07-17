<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\DemandeMateriel;
use App\Entity\Article;
use App\Entity\LigneDemande;
use App\Entity\MouvementStock;
use App\Entity\User;
use App\Enum\TypeMouvement;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class StockService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ArticleRepository $articleRepository,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Déduit le stock lors de la livraison d'une ligne de demande.
     * Utilise la quantité servie (ou demandée si non renseignée).
     */
    public function deduireStock(LigneDemande $ligne, User $operateur, DemandeMateriel $demande): void
    {
        $this->em->wrapInTransaction(function () use ($ligne, $operateur, $demande): void {
            $article = $ligne->getArticle();
            if ($article === null) {
                return;
            }

            $qty = $ligne->getQuantiteServie() > 0
                ? $ligne->getQuantiteServie()
                : $ligne->getQuantiteDemandee();

            $avant = $article->getStockQuantity();
            $apres = max(0, $avant - $qty);

            $article->setStockQuantity($apres);

            $mouvement = new MouvementStock();
            $mouvement->setType(TypeMouvement::SORTIE);
            $mouvement->setQuantite($qty);
            $mouvement->setQuantiteAvant($avant);
            $mouvement->setQuantiteApres($apres);
            $mouvement->setMotif(sprintf(
                'Livraison demande %s — %s',
                $demande->getReference(),
                $article->getName()
            ));
            $mouvement->setArticle($article);
            $mouvement->setOperateur($operateur);
            $mouvement->setDemande($demande);

            $this->em->persist($mouvement);

            $this->logger->info('Stock déduit', [
                'article' => $article->getReference(),
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
    public function ajouterStock(Article $article, int $qty, string $motif, User $operateur): void
    {
        $this->em->wrapInTransaction(function () use ($article, $qty, $motif, $operateur): void {
            $avant = $article->getStockQuantity();
            $apres = $avant + $qty;

            $article->setStockQuantity($apres);

            $mouvement = new MouvementStock();
            $mouvement->setType(TypeMouvement::ENTREE);
            $mouvement->setQuantite($qty);
            $mouvement->setQuantiteAvant($avant);
            $mouvement->setQuantiteApres($apres);
            $mouvement->setMotif($motif);
            $mouvement->setArticle($article);
            $mouvement->setOperateur($operateur);

            $this->em->persist($mouvement);
        });
    }

    /**
     * Ajuste le stock à une nouvelle valeur absolue.
     */
    public function ajustementStock(Article $article, int $newQty, string $motif, User $operateur): void
    {
        $this->em->wrapInTransaction(function () use ($article, $newQty, $motif, $operateur): void {
            $avant = $article->getStockQuantity();

            $article->setStockQuantity($newQty);

            $mouvement = new MouvementStock();
            $mouvement->setType(TypeMouvement::AJUSTEMENT);
            $mouvement->setQuantite(abs($newQty - $avant));
            $mouvement->setQuantiteAvant($avant);
            $mouvement->setQuantiteApres($newQty);
            $mouvement->setMotif($motif);
            $mouvement->setArticle($article);
            $mouvement->setOperateur($operateur);

            $this->em->persist($mouvement);

            $this->logger->info('Ajustement stock', [
                'article' => $article->getReference(),
                'avant'      => $avant,
                'apres'      => $newQty,
                'motif'      => $motif,
            ]);
        });
    }

    /**
     * Retourne la liste des articles en stock bas.
     *
     * @return Article[]
     */
    public function getArticlesStockBas(): array
    {
        return $this->articleRepository->findStockBas();
    }
}
