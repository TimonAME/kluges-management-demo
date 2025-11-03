<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

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

    //TODO: Write a function that gets the id, first name and last name of a given user
    public function findUserById(int $userId): array
    {
        return $this->getEntityManager()->createQuery('
        SELECT u.id, u.first_name, u.last_name
        FROM App\Entity\User u
        WHERE u.id = :userId')
            ->setParameter('userId', $userId)
            ->getArrayResult();
    }

    public function findUsersForNotifications(string $query, int $limit): array
    {
        return $this->getEntityManager()->createQuery('
        SELECT u
        FROM App\Entity\User u
        WHERE u.first_name LIKE :query
        OR u.last_name LIKE :query
        OR u.email LIKE :query
        ')
            ->setParameter("query", '%' . $query . '%')
            ->setMaxResults($limit)
            ->getArrayResult();
    }

    /**
     * Returns all birthdays that are between now and one month ahead
     * @return array
     */
    public function findUpcomingBirthdaysForDashboard(): array
    {
        $users = $this->getEntityManager()->createQuery('
            SELECT u.first_name, u.last_name, u.birthday, u.id
            FROM App\Entity\User u
            WHERE u.is_deleted IS NULL
            ORDER BY u.birthday ASC
            ')
            ->getArrayResult();

        return array_filter($users, function ($item) {

            $now = new \DateTime();
            $now->setTime(0, 0, 0);
            $oneMonthFromNow = (new \DateTime())->modify('+1 month');

            $birthday = $item['birthday'];
            $birthYear = $birthday->format('Y');
            $birthday->setDate($now->format('Y'), $birthday->format('m'), $birthday->format('d'));
            if ($birthday >= $now && $birthday <= $oneMonthFromNow) {
                $birthday->setDate($birthYear, $birthday->format('m'), $birthday->format('d'));
                return true;
            }
            return false;
        });
    }

    public function findTeachersBySubjects(array $subjectNames, string $searchTerm = '', string $sortBy = 'id', string $sortDirection = 'ASC', int $limit = 10, int $offset = 0): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // Convert the array to a comma-separated string for the SQL query
        $subjectNamesString = implode(',', array_map([$conn, 'quote'], $subjectNames));

        // Query to get the total count
        $countSql = '
        SELECT COUNT(DISTINCT u.id) as total
        FROM user u
        LEFT JOIN user_subject us ON u.id = us.user_id
        LEFT JOIN subject s ON us.subject_id = s.id
        WHERE u.roles LIKE :role
        AND s.name IN (' . $subjectNamesString . ')
        AND (u.first_name LIKE :searchTerm OR u.last_name LIKE :searchTerm)
    ';
        $countStmt = $conn->prepare($countSql);
        $countParams = [
            'role' => '%"ROLE_TEACHER"%',
            'searchTerm' => '%' . $searchTerm . '%'
        ];
        $totalCount = $countStmt->executeQuery($countParams)->fetchOne();

        // Query to get the paginated results
        $sql = '
        SELECT DISTINCT u.id, u.first_name, u.last_name, u.email, u.birthday, u.roles, 
            (SELECT GROUP_CONCAT(s2.name) 
             FROM subject s2 
             LEFT JOIN user_subject us2 ON s2.id = us2.subject_id 
             WHERE us2.user_id = u.id) as subject_names
        FROM user u
        LEFT JOIN user_subject us ON u.id = us.user_id
        LEFT JOIN subject s ON us.subject_id = s.id
        WHERE u.roles LIKE :role
            AND s.name IN (' . $subjectNamesString . ')
            AND (u.first_name LIKE :searchTerm OR u.last_name LIKE :searchTerm)
        GROUP BY u.id
        ORDER BY u.' . $sortBy . ' ' . $sortDirection . '
        LIMIT :limit OFFSET :offset
    ';
        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery([
            'role' => '%"ROLE_TEACHER"%',
            'searchTerm' => '%' . $searchTerm . '%',
            'limit' => $limit,
            'offset' => $offset
        ]);

        return [
            'total' => $totalCount,
            'teachers' => $resultSet->fetchAllAssociative()
        ];
    }

    public function searchUsers(string $searchTerm = '', string $role = '', string $sortBy = 'id', string $sortDirection = 'ASC', int $limit = 10, int $offset = 0): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // Query to get the total count
        $countSql = '
    SELECT COUNT(DISTINCT u.id) as total
    FROM user u
    LEFT JOIN user g ON u.guardian_id = g.id
    LEFT JOIN user_subject us ON u.id = us.user_id
    LEFT JOIN subject s ON us.subject_id = s.id
    WHERE (u.first_name LIKE :searchTerm OR u.last_name LIKE :searchTerm)
    ';
        if ($role) {
            $countSql .= ' AND u.roles LIKE :role';
        }
        $countStmt = $conn->prepare($countSql);
        $countParams = ['searchTerm' => '%' . $searchTerm . '%'];
        if ($role) {
            $countParams['role'] = '%"ROLE_' . strtoupper($role) . '"%';
        }
        $totalCount = $countStmt->executeQuery($countParams)->fetchOne();

        // Query to get the paginated results
        $sql = '
    SELECT u.id, u.first_name, u.last_name, u.email, g.email AS guardian_email, g.first_name as guardian_first_name, g.last_name as guardian_last_name, u.birthday, u.roles,
           GROUP_CONCAT(s.name) as subject_names,
           GROUP_CONCAT(s.color_hex_code) as subject_colors
    FROM user u
    LEFT JOIN user g ON u.guardian_id = g.id
    LEFT JOIN user_subject us ON u.id = us.user_id
    LEFT JOIN subject s ON us.subject_id = s.id
    WHERE (u.first_name LIKE :searchTerm OR u.last_name LIKE :searchTerm)
    ';
        if ($role) {
            $sql .= ' AND u.roles LIKE :role';
        }
        $sql .= ' GROUP BY u.id ORDER BY u.' . $sortBy . ' ' . $sortDirection . ' LIMIT :limit OFFSET :offset';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);

        if ($role) {
            $stmt->bindValue(':role', '%"ROLE_' . strtoupper($role) . '"%');
        }

        $resultSet = $stmt->executeQuery();

        return [
            'total' => $totalCount,
            'users' => $resultSet->fetchAllAssociative()
        ];
    }


    /**
     * @return User[] Returns an array of User objects
     */
    public function searchUsersForTodoByQuery($query, $limit = 10): array
    {
        $entityManager = $this->getEntityManager();
        $queryBuilder = $entityManager->createQuery('
        SELECT u
        FROM App\Entity\User u
        WHERE (u.first_name LIKE :query OR u.last_name LIKE :query)
        ORDER BY u.last_name ASC
        ')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit);
        return $queryBuilder->getResult();
    }

    public function findByIdForUserProfile($id)
    {
        $sql = '
            SELECT user.id, user.first_name, user.last_name, user.email, user.roles
            FROM App\Entity\User user
            WHERE user.id = :id
            AND user.is_deleted IS NULL';

        $query = $this->getEntityManager()->createQuery($sql);
        $query->setParameter('id', $id);

        return $query->getArrayResult()[0]; // Fetches as an associative array
    }

    public function findCurrentUser($userId)
    {
        return $this->createQueryBuilder('u')
            ->select('u.id, u.first_name, u.last_name, u.email')
            ->where('u.id = :id')
            ->setParameter('id', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
