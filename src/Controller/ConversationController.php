<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Repository\ConversationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ConversationController extends AbstractController
{
    private NormalizerInterface $normalizer;

    public function __construct(NormalizerInterface $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    //This function is used to render the chat page with all of their existing conversations
    //To use it, you need to send a GET request
    #[Route('/chat', name: 'app_conversation_index', methods: ['GET'])]
    public function index(ConversationRepository $conversationRepository): Response
    {
        return $this->render('chat/index.html.twig', [
            'conversations' => $this->normalizer->normalize($conversationRepository->findConversationsForUser($this->getUser()->getId()), null, ['groups' => ['conversation:basic', 'user:basic', 'message:read']]),
        ]);
    }

    //This function is used to render a chat conversation with all the messages
    //To use it, you need to send a GET request with the conversation id
    #[Route('/chat/{id}', name: 'app_conversation_show', methods: ['GET'])]
    public function show(Conversation $conversation, ConversationRepository $conversationRepository, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $otherUser = $conversation->getInitiator() === $this->getUser() ? $conversation->getRecipient() : $conversation->getInitiator();
        $entityManager->initializeObject($otherUser);

        return $this->render('chat/index.html.twig', [
            'currentUserId' => $this->getUser()->getId(),
            'otherUser' => $userRepository->findUserById($otherUser->getId()),
            'messages' => $conversationRepository->findMessagesForConversation($conversation->getId()),
            'conversation' => $conversation->getId(),
            'conversations' => $this->normalizer->normalize($conversationRepository->findConversationsForUser($this->getUser()->getId()), null, ['groups' => ['conversation:basic', 'user:basic', 'message:read']])
        ]);
    }
}
