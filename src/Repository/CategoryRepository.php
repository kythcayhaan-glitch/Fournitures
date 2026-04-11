<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Retourne les catégories avec le nombre de fournitures actives.
     *
     * @return array<array{category: Category, count: int}>
     */
    public function findWithFournitureCount(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c', 'COUNT(f.id) as fournitureCount')
            ->leftJoin('c.fournitures', 'f', 'WITH', 'f.isActive = true')
            ->groupBy('c.id')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne toutes les catégories triées par nom.
     *
     * @return Category[]
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
