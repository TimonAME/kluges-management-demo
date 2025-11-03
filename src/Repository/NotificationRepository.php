<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('n')
            ->select('n')
            ->getQuery()
            ->getArrayResult();
    }

    public function findAllUnreadWithTagsAndUsers(User $user): array
    {
        return $this->getEntityManager()->createQuery('
        SELECT n, u, t
        FROM App\Entity\Notification n
        LEFT JOIN n.users u
        LEFT JOIN n.notificationTags t
        WHERE :user MEMBER OF n.users
        AND n.isRead = false
        ORDER BY n.date_created DESC
    ')
            ->setParameter("user", $user)
            ->getArrayResult();
    }

    public function findForDashboard(User $user): array
    {

        return $this->getEntityManager()->createQuery('
        SELECT n, t
        FROM App\Entity\Notification n
        LEFT JOIN n.notificationTags t
        WHERE :user MEMBER OF n.users
        AND n.isRead = false
        ORDER BY n.date_created DESC
        ')
            ->setParameter("user", $user)
            ->getArrayResult();
    }



    public function findByCategory(User $user, string $category): array
    {
        $qb = $this->createQueryBuilder('n')
            ->leftJoin('n.notificationTags', 't')
            ->leftJoin('n.users', 'u')
            ->addSelect('t', 'u')
            ->where(':user MEMBER OF n.users')
            ->setParameter('user', $user);

        if ($category === 'unread') {
            $qb->andWhere('n.isRead = false');
        } elseif ($category === 'completed') {
            $qb->andWhere('n.isRead = true');
        } else {
            $qb->andWhere('t.name = :category')
                ->setParameter('category', $category);
        }

        return $qb->orderBy('n.date_created', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    public function findNotificationsForUser(User $user, bool $isRead = null): array
    {
        $query = $this->getEntityManager()->createQuery('
        SELECT n FROM App\Entity\Notification n
        WHERE :user MEMBER OF n.users'
    .(($isRead !== null) ? ' AND n.isRead = :isRead' : ''))
    ->setParameter('user', $user);

        if ($isRead !== null) {
            $query->setParameter('isRead', $isRead);
        }

        return $query->getResult();
    }

    //    /**
    //     * @return Notification[] Returns an array of Notification objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('n')
    //            ->andWhere('n.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('n.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Notification
    //    {
    //        return $this->createQueryBuilder('n')
    //            ->andWhere('n.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
