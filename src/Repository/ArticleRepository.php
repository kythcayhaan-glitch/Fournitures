<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * Retourne les articles dont le stock est en dessous du minimum.
     *
     * @return Article[]
     */
    public function findStockBas(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.stockQuantity <= a.stockMinimum')
            ->leftJoin('a.category', 'c')
            ->addSelect('c')
            ->orderBy('a.stockQuantity', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche d'articles par nom ou référence.
     *
     * @return Article[]
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.name LIKE :q OR a.reference LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->leftJoin('a.category', 'c')
            ->addSelect('c')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne la query builder pour la liste admin (avec filtres).
     */
    public function createAdminQueryBuilder(?string $search = null, ?int $categoryId = null): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.category', 'c')
            ->addSelect('c')
            ->orderBy('a.name', 'ASC');

        if ($search) {
            $qb->andWhere('a.name LIKE :search OR a.reference LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($categoryId) {
            $qb->andWhere('a.category = :cat')
               ->setParameter('cat', $categoryId);
        }

        return $qb;
    }
}
