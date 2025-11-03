<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Conversation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ConversationProvider implements ProviderInterface
{
    private NormalizerInterface $normalizer;
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(NormalizerInterface $normalizer, EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->normalizer = $normalizer;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $route = $context['request']?->attributes->get('_route');
        $currentUser = $this->security->getUser();
        $conversationRepository = $this->entityManager->getRepository(Conversation::class);

        switch ($route) {
            case 'getExistingChatsForUser':
               // return $context;
                return $this->normalizer->normalize($conversationRepository->findConversationsForUser($currentUser->getId()), null, ['groups' => ['conversation:basic', 'user:basic', 'message:read']]);
        }
        return null;
    }
}