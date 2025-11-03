<?php

namespace App\Repository;

use App\Entity\Todo;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Todo>
 */
class TodoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Todo::class);
    }

    /**
     * @return Todo[] Returns an array of Todo objects
     */
    public function findForDashboard(User $user): array
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery('
            SELECT t.id, t.title, t.description, t.dueDate, c.first_name, c.last_name, c.id AS creator_id
            FROM App\Entity\Todo t
            LEFT JOIN t.creator c
            LEFT JOIN t.userTodos d
            LEFT JOIN d.user u
            WHERE (t.creator = :user OR :user = d.user)
            AND d.isChecked = false
            AND (t.dueDate <= :today OR t.dueDate IS NULL)
            GROUP BY t.id
            ORDER BY
                CASE
                    WHEN t.dueDate = :today THEN 1
                    WHEN t.dueDate IS NULL THEN 2
                    ELSE 3
                END, t.dueDate DESC
            ')
            ->setParameter('user', $user->getId())
            ->setParameter('today', (new \DateTime())->format("Y-m-d H:i:s"));
        return $query->getResult();
    }

    public function findForUser($user): array
    {
        // TODO: return creator and assigned users
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery('
        SELECT t.id, t.title, t.description, t.dueDate
        FROM App\Entity\Todo t
        LEFT JOIN t.creator c
        LEFT JOIN t.userTodos d
        LEFT JOIN d.user u
        WHERE (t.creator = :user OR :user = d.user)
        GROUP BY t.id
        ')
            ->setParameter('user', $user->getId());
        return $query->getResult();
    }

    /**
     * @return Todo[] Returns an array of Todo objects
     */
    public function getTodosByQuery($user, $query): array {
        $entityManager = $this->getEntityManager();
        $queryBuilder = $entityManager->createQuery('
        SELECT t
        FROM App\Entity\Todo t
        LEFT JOIN t.userTodos u
        WHERE (t.title LIKE :query OR t.description LIKE :query) AND (t.creator = :user OR :user = u.user)
        ORDER BY t.title ASC
        ')
            ->setParameter('query', '%' . $query . '%')
            ->setParameter('user', $user->getId());
        return $queryBuilder->getResult();
    }


    /***** Methods for the Different Kategories *****/
    public function findTodayTodos($user): array
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery('
        SELECT t.id, t.title, t.description, t.dueDate, c.first_name, c.last_name, c.id AS creator_id
        FROM App\Entity\Todo t
        LEFT JOIN t.creator c
        LEFT JOIN t.userTodos d
        LEFT JOIN d.user u
        WHERE (t.creator = :user OR :user = d.user)
        AND d.isChecked = false
        AND (t.dueDate <= :today OR t.dueDate IS NULL)
        GROUP BY t.id
        ORDER BY 
            CASE 
                WHEN t.dueDate = :today THEN 1
                WHEN t.dueDate IS NULL THEN 2
                ELSE 3
            END, t.dueDate DESC
        ')
            ->setParameter('user', $user->getId())
            ->setParameter('today', (new \DateTime())->format("Y-m-d H:i:s"));
        return $query->getResult();
    }

    public function findUpcomingTodos($user): array
    {
        // todos from the future and with no due date
        return $this->createQueryBuilder('t')
            ->select('t.id, t.title, t.description, t.dueDate, c.first_name, c.last_name, c.id AS creator_id')
            ->leftJoin('t.userTodos', 'ut')
            ->leftJoin('t.creator', 'c')
            ->where('(t.creator = :user OR ut.user = :user)')
            ->andWhere('ut.isChecked = false')
            ->andWhere('(
                t.dueDate IS NULL OR 
                t.dueDate > :today
            )')
            ->setParameter('user', $user)
            ->setParameter('today', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    public function findInboxTodos($user): array
    {
        return $this->createQueryBuilder('t')
            ->select('t.id, t.title, t.description, t.dueDate, c.first_name, c.last_name, c.id AS creator_id')
            ->leftJoin('t.userTodos', 'ut')
            ->leftJoin('t.creator', 'c')
            ->where('ut.user = :user')
            ->andWhere('t.creator != :user')
            ->andWhere('ut.isChecked = false')
            ->setParameter('user', $user)
            ->orderBy('t.dueDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findCompletedTodos($user): array
    {
        return $this->createQueryBuilder('t')
            ->select('t.id, t.title, t.description, t.dueDate, c.first_name, c.last_name, c.id AS creator_id')
            ->leftJoin('t.userTodos', 'ut')
            ->leftJoin('t.creator', 'c')
            ->where('(t.creator = :user OR ut.user = :user)')
            ->andWhere('ut.isChecked = true')
            ->orderBy('t.dueDate', 'DESC')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function searchTodos($user, $query): array
    {
        return $this->createQueryBuilder('t')
            ->select('t.id, t.title, t.description, t.dueDate, c.first_name, c.last_name, c.id AS creator_id')
            ->leftJoin('t.userTodos', 'ut')
            ->leftJoin('t.creator', 'c')
            ->where('(t.title LIKE :query OR t.description LIKE :query) AND (t.creator = :user OR ut.user = :user)')
            ->andWhere('ut.isChecked = false')
            ->orderBy('t.dueDate', 'DESC')
            ->setParameter('query', '%' . $query . '%')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findTodosForUser(User $user, ?bool $isDone = null): array
    {
        $dql = '
            SELECT t, ut.isChecked AS isChecked
            FROM App\Entity\Todo t
            LEFT JOIN t.userTodos ut
            WHERE (t.creator = :user OR ut.user = :user)
        ';

        if ($isDone !== null) {
            $dql .= ' AND ut.isChecked = :isDone';
        }

        $dql .= ' ORDER BY t.dueDate DESC';

        $query = $this->getEntityManager()->createQuery($dql)
            ->setParameter('user', $user);

        if ($isDone !== null)
        {
            $query->setParameter('isDone', $isDone);
        }

        $results = $query->getResult();

        // Map the isChecked attribute to the Todo object
        foreach ($results as &$result) {
            $result[0]->isChecked = $result['isChecked'];
        }

        return array_map(fn($result) => $result[0], $results);
    }
}
