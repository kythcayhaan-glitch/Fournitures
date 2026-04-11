<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\DemandeMateriel;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DemandeMateriel>
 */
class DemandeMaterielRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DemandeMateriel::class);
    }

    /**
     * Retourne les demandes d'un utilisateur avec filtres optionnels.
     */
    public function findByUserWithFilters(User $user, ?string $statut = null, ?\DateTimeImmutable $from = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.requester = :user')
            ->setParameter('user', $user)
            ->orderBy('d.requestedAt', 'DESC');

        if ($statut) {
            $qb->andWhere('d.statut = :statut')
               ->setParameter('statut', $statut);
        }

        if ($from) {
            $qb->andWhere('d.requestedAt >= :from')
               ->setParameter('from', $from);
        }

        return $qb;
    }

    /**
     * Retourne toutes les demandes en attente (pour les managers).
     *
     * @return DemandeMateriel[]
     */
    public function findPending(): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.statut = :statut')
            ->setParameter('statut', 'pending')
            ->leftJoin('d.requester', 'u')
            ->addSelect('u')
            ->orderBy('d.requestedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne le QueryBuilder pour la liste manager avec filtres.
     */
    public function createManagerQueryBuilder(?string $statut = null, ?\DateTimeImmutable $from = null, ?\DateTimeImmutable $to = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.requester', 'u')
            ->addSelect('u')
            ->orderBy('d.requestedAt', 'DESC');

        if ($statut) {
            $qb->andWhere('d.statut = :statut')
               ->setParameter('statut', $statut);
        }

        if ($from) {
            $qb->andWhere('d.requestedAt >= :from')
               ->setParameter('from', $from);
        }

        if ($to) {
            $qb->andWhere('d.requestedAt <= :to')
               ->setParameter('to', $to);
        }

        return $qb;
    }

    /**
     * Retourne les demandes par statut et utilisateur.
     *
     * @return DemandeMateriel[]
     */
    public function findByStatutAndUser(string $statut, User $user): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.statut = :statut')
            ->andWhere('d.requester = :user')
            ->setParameter('statut', $statut)
            ->setParameter('user', $user)
            ->orderBy('d.requestedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les demandes anciennes d'un statut donné (pour purge).
     *
     * @return DemandeMateriel[]
     */
    public function findOlderThan(string $statut, \DateTimeImmutable $before): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.statut = :statut')
            ->andWhere('d.requestedAt < :before')
            ->setParameter('statut', $statut)
            ->setParameter('before', $before)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne le nombre de demandes par statut pour un utilisateur.
     *
     * @return array<string, int>
     */
    public function countByStatutForUser(User $user): array
    {
        $results = $this->createQueryBuilder('d')
            ->select('d.statut, COUNT(d.id) as cnt')
            ->andWhere('d.requester = :user')
            ->setParameter('user', $user)
            ->groupBy('d.statut')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['statut']] = (int) $row['cnt'];
        }
        return $counts;
    }

    /**
     * Compte le dernier index de référence pour aujourd'hui.
     */
    public function countTodayDemandes(): int
    {
        $today = new \DateTimeImmutable('today');
        $tomorrow = $today->modify('+1 day');

        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->andWhere('d.requestedAt >= :today')
            ->andWhere('d.requestedAt < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
