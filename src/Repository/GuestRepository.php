<?php

namespace App\Repository;

use App\Entity\Guest;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Guest>
 */
class GuestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Guest::class);
    }

    /**
     * Trouve tous les invités ajoutés depuis une date donnée
     */
    public function findGuestsSince(DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('g')
            ->join('g.eventRegistration', 'e')
            ->andWhere('e.registeredAt >= :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les invités ajoutés entre deux dates
     */
    public function findGuestsBetween(DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        return $this->createQueryBuilder('g')
            ->join('g.eventRegistration', 'e')
            ->andWhere('e.registeredAt >= :start')
            ->andWhere('e.registeredAt <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }
}
