<?php

namespace App\Repository;

use App\Entity\Conversation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    //This function is used to find all the users for a dropdown selection
    public function findUsersForDropdown(int $userId, string $searchTerm): array
    {
        return $this->getEntityManager()->createQuery('
            SELECT u
            FROM App\Entity\User u
            WHERE u.id != :userId AND CONCAT(u.first_name, \' \', u.last_name) LIKE :searchTerm OR u.first_name LIKE :searchTerm OR u.last_name LIKE :searchTerm OR u.email LIKE :searchTerm
            AND u.is_deleted != true
            ')
            ->setParameter('userId', $userId)
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->getResult();
    }

    //This function is used to find all the conversations for a user
    public function findConversationsForUser(int $userId): array
    {
        return $this->getEntityManager()->createQuery('
        SELECT c, i, r, m,
            (CASE
                WHEN i.id = :userId THEN CONCAT(r.first_name, \' \', r.last_name)
                ELSE CONCAT(i.first_name, \' \', i.last_name)
            END) AS partnerUser
        FROM App\Entity\Conversation c
        LEFT JOIN c.initiator i
        LEFT JOIN c.recipient r
        LEFT JOIN c.last_message m
        WHERE c.initiator = :userId OR c.recipient = :userId
        ORDER BY m.created_at DESC
    ')
            ->setParameter('userId', $userId)
            ->getResult();
    }

    //TODO: Write a function that gets all the messages for a conversation in the same query format as above
    public function findMessagesForConversation(int $conversationId): array
    {
        return $this->getEntityManager()->createQuery('
        SELECT m, s
        FROM App\Entity\Message m
        LEFT JOIN m.sender s
        WHERE m.conversation = :conversationId')
            ->setParameter('conversationId', $conversationId)
            ->getArrayResult();
    }
}
