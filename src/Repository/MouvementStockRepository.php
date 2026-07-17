<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Article;
use App\Entity\MouvementStock;
use App\Enum\TypeMouvement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MouvementStock>
 */
class MouvementStockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MouvementStock::class);
    }

    /**
     * Retourne l'historique des mouvements d'un article.
     *
     * @return MouvementStock[]
     */
    public function findByArticle(Article $article, int $limit = 20): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.article = :a')
            ->setParameter('a', $article)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les mouvements récents tous articles confondus.
     *
     * @return MouvementStock[]
     */
    public function findToday(): array
    {
        $debut = new \DateTimeImmutable('today');
        $fin   = new \DateTimeImmutable('tomorrow');

        return $this->createQueryBuilder('m')
            ->leftJoin('m.article', 'a')->addSelect('a')
            ->leftJoin('m.operateur', 'u')->addSelect('u')
            ->andWhere('m.createdAt >= :debut')
            ->andWhere('m.createdAt < :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecent(int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.article', 'a')
            ->addSelect('a')
            ->leftJoin('m.operateur', 'u')
            ->addSelect('u')
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne le QueryBuilder pour l'historique admin avec filtres.
     */
    public function createHistoriqueQueryBuilder(
        ?TypeMouvement $type = null,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.article', 'a')
            ->addSelect('a')
            ->leftJoin('m.operateur', 'u')
            ->addSelect('u')
            ->orderBy('m.createdAt', 'DESC');

        if ($type) {
            $qb->andWhere('m.type = :type')
               ->setParameter('type', $type);
        }

        if ($from) {
            $qb->andWhere('m.createdAt >= :from')
               ->setParameter('from', $from);
        }

        if ($to) {
            $qb->andWhere('m.createdAt <= :to')
               ->setParameter('to', $to);
        }

        return $qb;
    }
}
