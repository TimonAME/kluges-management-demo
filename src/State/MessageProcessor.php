<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Message;
use App\Entity\Conversation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class MessageProcessor implements ProcessorInterface
{
    private Security $security;
    private EntityManagerInterface $entityManager;

    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Message|null
    {
        $decodedContent = json_decode($context['request']?->getContent(), true);
        $user = $this->security->getUser();

        if (!$user instanceof UserInterface) {
            throw new \LogicException('No authenticated user found.');
        }

        $conversation = $this->entityManager->getRepository(Conversation::class)->find($data->getConversation()?->getId());

        if (!$conversation) {
            throw new \InvalidArgumentException('Invalid conversation ID.');
        }

        $message = new Message();
        $message->setConversation($conversation);
        $message->setSender($user);
        $message->setContent($decodedContent['message']);
        $message->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        $conversation->setLastMessage($message);
        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        return $message;
    }
}