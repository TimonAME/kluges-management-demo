<?php

namespace App\Repository;

use App\Entity\Subject;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subject>
 */
class SubjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subject::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('s')
            ->select('s')
            ->getQuery()
            ->getResult();
    }

    // function to get name and hex code of all subjects
    public function findAllNameAndHexCode(): array
    {
        return $this->createQueryBuilder('s')
            ->select('s.id', 's.name', 's.color_hex_code')
            ->getQuery()
            ->getResult();
    }

    /**
     * Findet Lehrer nach Fachname
     * 
     * @param string $subjectName Der Name des Fachs
     * @return array Ein Array mit Lehrern, die das angegebene Fach unterrichten
     */
    public function findTeachersBySubjectName(string $subjectName): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT u.id, u.first_name, u.last_name, u.email, u.working_hours, s.name as subject_name, s.color_hex_code
            FROM user u
            JOIN user_subject us ON u.id = us.user_id
            JOIN subject s ON us.subject_id = s.id
            WHERE s.name = :subject_name
            AND u.roles LIKE :role
            ORDER BY u.last_name ASC, u.first_name ASC
        ';

        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery([
            'subject_name' => $subjectName,
            'role' => '%ROLE_TEACHER%'
        ]);

        $teachers = $resultSet->fetchAllAssociative();

        // Format the working hours data
        foreach ($teachers as &$teacher) {
            if (isset($teacher['working_hours'])) {
                $teacher['working_hours'] = json_decode($teacher['working_hours'], true);
            } else {
                $teacher['working_hours'] = [
                    'template' => [],
                    'individual' => []
                ];
            }
        }

        return $teachers;
    }

    /**
     * Sucht nach Schülern basierend auf Suchbegriff und optional Fach
     * 
     * @param string $searchTerm Der Suchbegriff
     * @param string|null $subjectName Optional: Name des Fachs
     * @return array Ein Array mit Schülern, die den Suchkriterien entsprechen
     */
    public function findStudentsBySearchAndSubject(string $searchTerm, ?string $subjectName = null): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $params = [
            'searchTerm' => '%' . $searchTerm . '%',
            'role' => '%ROLE_STUDENT%'
        ];

        $subjectFilter = '';
        if ($subjectName) {
            $subjectFilter = 'AND s.name = :subjectName';
            $params['subjectName'] = $subjectName;
        }

        $sql = "
            SELECT u.id, u.first_name, u.last_name, 
                   GROUP_CONCAT(s.name) as subject_names,
                   GROUP_CONCAT(s.color_hex_code) as subject_colors
            FROM user u
            LEFT JOIN user_subject us ON u.id = us.user_id
            LEFT JOIN subject s ON us.subject_id = s.id
            WHERE u.roles LIKE :role
            AND (u.first_name LIKE :searchTerm OR u.last_name LIKE :searchTerm)
            $subjectFilter
            GROUP BY u.id
            ORDER BY u.last_name ASC, u.first_name ASC
            LIMIT 10
        ";

        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery($params);

        return $resultSet->fetchAllAssociative();
    }
}
