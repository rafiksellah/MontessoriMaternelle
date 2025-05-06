<?php

namespace App\Repository;

use App\Entity\EventRegistration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
}