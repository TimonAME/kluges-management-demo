<?php

namespace App\Repository;

use App\Entity\NotificationTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NotificationTag>
 */
class NotificationTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationTag::class);
    }

    public function findAllTags(): array
    {
        return $this->getEntityManager()->createQuery('
        SELECT t
        FROM App\Entity\NotificationTag t
        ')
            ->getArrayResult();
    }

    public function findTagsForNotifications(string $query, int $limit): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
        SELECT id, name, hex_color
        FROM notification_tag
        WHERE name LIKE :query
        LIMIT :limit
    ';

        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery([
            'query' => '%' . $query . '%',
            'limit' => $limit
        ]);

        // returns an array of arrays (i.e. a raw data set)
        return $resultSet->fetchAllAssociative();
    }

    public function searchTags(string $query, int $limit = 10)
    {

        $this->getEntityManager()->createQuery('
        SELECT nt
        FROM App\Entity\NotificationTag nt
        WHERE nt.name LIKE :query
        LIMIT :limit
        ')
            ->setParameter("query", '%' . $query . '%')
            ->setParameter("limit", $limit)
            ->getArrayResult();
    }
}
