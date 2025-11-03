<?php

namespace App\Repository;

use App\Entity\UserTodo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserTodo>
 */
class UserTodoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserTodo::class);
    }

    public function getByTodoAndUser($user, $todo): ?int
    {
        $entityManager = $this->getEntityManager();
        $queryBuilder = $entityManager->createQuery('
        SELECT s.id
        FROM App\Entity\UserTodo s
        WHERE s.user = :user AND s.todo = :todo
        ')
            ->setParameter('user', $user->getId())
            ->setParameter('todo', $todo->getId())
            ->getOneOrNullResult();
        if ($queryBuilder === null) {
            return null; // No matching UserTodo found
        }

        // Extract and return the ID from the result array
        return (int) $queryBuilder['id'];
    }
}
