<?php

namespace App\Repository;

use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function findMessageByDateTimeAndSender(\DateTimeImmutable $dateTime, int $senderId): ?Message
    {
        return $this->getEntityManager()->createQuery('
            SELECT m
            FROM App\Entity\Message m
            WHERE m.created_at = :dateTime AND m.sender = :senderId
            ')
            ->setParameter('dateTime', $dateTime)
            ->setParameter('senderId', $senderId)
            ->getOneOrNullResult();
    }
}
