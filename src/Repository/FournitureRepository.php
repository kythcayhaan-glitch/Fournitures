<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Fourniture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Fourniture>
 */
class FournitureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Fourniture::class);
    }

    /**
     * Retourne les fournitures dont le stock est en dessous du minimum.
     *
     * @return Fourniture[]
     */
    public function findStockBas(): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.stockQuantity <= f.stockMinimum')
            ->andWhere('f.isActive = true')
            ->leftJoin('f.category', 'c')
            ->addSelect('c')
            ->orderBy('f.stockQuantity', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les fournitures actives d'une catégorie.
     *
     * @return Fourniture[]
     */
    public function findActiveByCategory(int $categoryId): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.category = :cat')
            ->andWhere('f.isActive = true')
            ->setParameter('cat', $categoryId)
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche de fournitures par nom ou référence.
     *
     * @return Fourniture[]
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.name LIKE :q OR f.reference LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->andWhere('f.isActive = true')
            ->leftJoin('f.category', 'c')
            ->addSelect('c')
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne la query builder pour la liste admin (avec filtres).
     */
    public function createAdminQueryBuilder(?string $search = null, ?int $categoryId = null, ?bool $isActive = null): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('f')
            ->leftJoin('f.category', 'c')
            ->addSelect('c')
            ->orderBy('f.name', 'ASC');

        if ($search) {
            $qb->andWhere('f.name LIKE :search OR f.reference LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($categoryId) {
            $qb->andWhere('f.category = :cat')
               ->setParameter('cat', $categoryId);
        }

        if ($isActive !== null) {
            $qb->andWhere('f.isActive = :active')
               ->setParameter('active', $isActive);
        }

        return $qb;
    }
}
