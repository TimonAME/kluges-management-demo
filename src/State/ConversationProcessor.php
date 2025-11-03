<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Conversation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ConversationProcessor implements ProcessorInterface
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Conversation|null|int|array|string
    {
        $currentUser = $this->security->getUser();
        $recipient = $this->entityManager->getRepository(User::class)->find($data->getRecipient()?->getId());

        if ($recipient) {
            $conversation = new Conversation();
            $conversation->setInitiator($currentUser);
            $conversation->setRecipient($recipient);

            $this->entityManager->persist($conversation);
            $this->entityManager->flush();
            return $conversation;
        }
        return null;
    }
}