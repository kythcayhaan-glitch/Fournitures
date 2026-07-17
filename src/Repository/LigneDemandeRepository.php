<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\LigneDemande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LigneDemande>
 */
class LigneDemandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LigneDemande::class);
    }

    public function topArticlesDemandes(int $limit = 10): array
    {
        return $this->createQueryBuilder('l')
            ->select('a.name, SUM(l.quantiteDemandee) as total')
            ->join('l.article', 'a')
            ->groupBy('a.id')
            ->orderBy('total', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
