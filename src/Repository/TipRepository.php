<?php

namespace App\Repository;

use App\Entity\Tip;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tip>
 */
class TipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tip::class);
    }

    public function findTipsForUser(User $user, bool $isRead = null): array
    {
        $query = $this->getEntityManager()->createQuery(
            '
        SELECT t, n FROM App\Entity\Tip t
        JOIN t.tipUsers n
        join n.user u
        WHERE u.id = :user'
            . (($isRead !== null) ? (($isRead) ?  " AND n.readAt IS NOT NULL":" AND n.readAt IS NULL") : '')
        )->setParameter('user', $user->getId());


        return $query->getResult();
    }

    // function to get all Tips in an array with id, message, and role
    public function getAllTips(): array
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery(
            'SELECT 
            t.id, 
            t.title,
            t.message,
            t.creationDate,
            tc.name as category,
            u.first_name as creatorFirstName,
            u.last_name as creatorLastName
        FROM App\Entity\Tip t
        JOIN t.tipCategory tc
        JOIN t.creator u
        ORDER BY t.creationDate DESC'
        );
        return $query->getResult();
    }

    public function getTipAndReadDate($user)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery('
        SELECT
            t.id,
            t.message,
            t.title,
            t.creationDate,
            tc.name as category,
            u.first_name as creatorFirstName,
            u.last_name as creatorLastName,
            s.readAt
        FROM App\Entity\Tip t
        JOIN t.tipCategory tc
        JOIN t.creator u
        LEFT JOIN t.tipUsers s WITH s.user = :user
        ORDER BY t.creationDate DESC
    ')
            ->setParameter('user', $user);
        return $query->getResult();
    }
}
