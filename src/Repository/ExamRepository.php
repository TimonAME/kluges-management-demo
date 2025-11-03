<?php

namespace App\Repository;

use App\Entity\Exam;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Exam>
 */
class ExamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Exam::class);
    }

    public function findForDashboard(User $user): array
    {
        $inOneMonth = new \DateTime('+1 month');
        $today = new \DateTime('-1 day');

        if (in_array('ROLE_STUDENT', $user->getRoles())) {
            return $this->getEntityManager()->createQuery('
            SELECT u, e, s
            FROM App\Entity\Exam e
            LEFT JOIN e.user_taking_exam u
            LEFT JOIN e.subject s
            WHERE e.date >= :today AND u.id = :userId
            ORDER BY e.date ASC')
                ->setParameter('today', $today)
                ->setParameter('userId', $user->getId())
                ->getResult(Query::HYDRATE_ARRAY);
        }

        if (in_array('ROLE_GUARDIAN', $user->getRoles())) {
            $wards = $user->getWards();
            $wardIds = array_map(function ($ward) {
                return $ward->getId();
            }, $wards->toArray());

            return $this->getEntityManager()->createQuery('
        SELECT u, e, s
        FROM App\Entity\Exam e
        LEFT JOIN e.user_taking_exam u
        LEFT JOIN e.subject s
        WHERE e.date >= :today AND u.id IN (:wardIds)
        ORDER BY e.date ASC')
                ->setParameter('today', $today)
                ->setParameter('wardIds', $wardIds)
                ->getResult(Query::HYDRATE_ARRAY);
        }
        return $this->getEntityManager()->createQuery('
        SELECT u, e, s
        FROM App\Entity\Exam e
        LEFT JOIN e.user_taking_exam u
        LEFT JOIN e.subject s
        WHERE e.date >= :today AND e.date <= :inOneMonth
        ORDER BY e.date ASC
        ')
            ->setParameter('inOneMonth', $inOneMonth) // No colon in 'setParameter'
            ->setParameter('today', $today)
            ->getResult(Query::HYDRATE_ARRAY);
    }
}
