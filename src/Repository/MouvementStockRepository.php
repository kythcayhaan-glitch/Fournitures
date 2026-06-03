<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Fourniture;
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
     * Retourne l'historique des mouvements d'une fourniture.
     *
     * @return MouvementStock[]
     */
    public function findByFourniture(Fourniture $fourniture, int $limit = 20): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.fourniture = :f')
            ->setParameter('f', $fourniture)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les mouvements récents toutes fournitures confondues.
     *
     * @return MouvementStock[]
     */
    public function findToday(): array
    {
        $debut = new \DateTimeImmutable('today');
        $fin   = new \DateTimeImmutable('tomorrow');

        return $this->createQueryBuilder('m')
            ->leftJoin('m.fourniture', 'f')->addSelect('f')
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
            ->leftJoin('m.fourniture', 'f')
            ->addSelect('f')
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
            ->leftJoin('m.fourniture', 'f')
            ->addSelect('f')
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
