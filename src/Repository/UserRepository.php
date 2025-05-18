<?php

namespace App\Repository;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Trouve tous les utilisateurs inscrits depuis une date donnée
     */
    public function findUsersSince(DateTimeImmutable $date): array
    {
        // Remarque: cette méthode est un exemple et nécessite un champ de date de création sur l'entité User
        // Si vous n'avez pas ce champ, vous devrez adapter cette méthode
        return $this->createQueryBuilder('u')
            // Supposons que vous avez un champ 'createdAt' ou similaire
            ->andWhere('u.createdAt >= :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les utilisateurs créés entre deux dates
     */
    public function findUsersBetween(DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        // Remarque: cette méthode est un exemple et nécessite un champ de date de création sur l'entité User
        // Si vous n'avez pas ce champ, vous devrez adapter cette méthode
        return $this->createQueryBuilder('u')
            ->andWhere('u.createdAt >= :start')
            ->andWhere('u.createdAt <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }
}
