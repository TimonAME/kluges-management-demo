<?php

namespace App\Repository;

use App\Entity\TipUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TipUser>
 */
class TipUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TipUser::class);
    }

    public function getByTipAndUser($user, $tip): ?int
    {
        $entityManager = $this->getEntityManager();
        $queryBuilder = $entityManager->createQuery('
        SELECT s.id
        FROM App\Entity\TipUser s
        WHERE s.user = :user AND s.tip = :tip
        ')
            ->setParameter('user', $user->getId())
            ->setParameter('tip', $tip->getId())
            ->getOneOrNullResult();
        if ($queryBuilder === null) {
            return null; // No matching UserTodo found
        }

        // Extract and return the ID from the result array
        return (int) $queryBuilder['id'];
    }
}
