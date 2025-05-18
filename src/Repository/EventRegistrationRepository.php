<?php

namespace App\Repository;

use DateTimeImmutable;
use App\Entity\EventRegistration;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<EventRegistration>
 */
class EventRegistrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventRegistration::class);
    }

    public function findByReminderNotSent(): array
    {
        return $this->createQueryBuilder('er')
            ->where('er.reminderSent = :reminderSent')
            ->setParameter('reminderSent', false)
            ->getQuery()
            ->getResult();
    }

    public function findRegistrationsSince(DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.registeredAt >= :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les inscriptions entre deux dates
     */
    public function findRegistrationsBetween(DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.registeredAt >= :start')
            ->andWhere('e.registeredAt <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les inscriptions liées à un compte utilisateur
     * Utilisez une jointure explicite pour accéder à la relation user
     */
    public function findRegistrationsWithAccount(): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.user', 'u') // Jointure explicite vers l'utilisateur
            ->getQuery()
            ->getResult();
    }
}
