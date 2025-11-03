<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\NotificationTag;
use App\Entity\User;
use App\Repository\NotificationRepository;
use App\Repository\NotificationTagRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class NotificationController extends AbstractController
{
    #[Route('/benachrichtigungen', name: 'app_benachrichtigungen', methods: ['GET'])]
    public function index(NotificationRepository $notificationRepository, NotificationTagRepository $notificationTagRepository): Response
    {
        return $this->render('notification/index.html.twig', [
            'notifications' => $notificationRepository->findAllUnreadWithTagsAndUsers($this->getUser()),
            'allTags' => $notificationTagRepository->findAllTags(),
        ]);
    }

    #[Route('/notification', name: 'app_notification_index', methods: ['GET'])]
    public function index2(NotificationRepository $notificationRepository): Response
    {
        return $this->render('notification/index.html.twig', [
            'notifications' => $notificationRepository->findAll(),
        ]);
    }

    #[Route('/api/notification', name: 'app_notification_new', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $data = json_decode($request->getContent(), true);

        $notification = new Notification();
        $notification->setMessage($data['notification']['message']);
        $notification->setIsRead($data['notification']['read']);
        $notification->setDateCreated(new \DateTime($data['notification']['date']));

        // Set users
        foreach ($data['notification']['users'] as $userId) {
            $user = $entityManager->getRepository(User::class)->find($userId);
            if ($user instanceof User) {
                $notification->addUser($user);
            }
        }

        // Set tags
        foreach ($data['notification']['tags'] as $tagId) {
            $tag = $entityManager->getRepository(NotificationTag::class)->find($tagId);
            if ($tag instanceof NotificationTag) {
                $notification->addNotificationTag($tag);
            }
        }

        $entityManager->persist($notification);
        $entityManager->flush();


        // Map users and tags to array of objects
        $users = array_map(function ($user) {
            return [
                'id' => $user->getId(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
            ];
        }, $notification->getUsers()->toArray());

        $tags = array_map(function ($tag) {
            return [
                'id' => $tag->getId(),
                'name' => $tag->getName(),
                'hex_color' => $tag->getHexColor(),
            ];
        }, $notification->getNotificationTags()->toArray());

        return new JsonResponse(
            [
                'success' => true,
                'notification' => [
                    'id' => $notification->getId(),
                    'message' => $notification->getMessage(),
                    'users' => $users,
                    'notificationTags' => $tags,
                    'is_read' => $notification->getIsRead(),
                    'date_created' => $notification->getDateCreated()->format('c'),
                ]
            ],
            200
        );
    }

    #[Route('/api/notification/{id}', name: 'app_notification_get', methods: ['GET'])]
    public function read(Notification $notification, SerializerInterface $serializer): Response
    {
        $jsonContent = $serializer->serialize($notification, 'json', ['groups' => ['notification_basic_view', 'notification_user_view']]);
        return JsonResponse::fromJsonString($jsonContent);
    }

    #[Route('/api/notification/', name: 'app_notification_getForDashbaord', methods: ['GET'])]
    public function readAll(NotificationRepository $notificationRepository): Response
    {
        return $this->json(
            [
                'notifications' => $notificationRepository->findAll(),
            ]
        );
    }

    #[Route('/api/notification/{id}', name: 'app_notification_edit', methods: ['PUT'])]
    public function update(
        Request                $request,
        Notification           $notification,
        EntityManagerInterface $entityManager,
        SerializerInterface    $serializer
    ): Response
    {
        //deserialize and update the existing entity
        $serializer->deserialize(
            $request->getContent(),
            Notification::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $notification]
        );

        $entityManager->flush();

        $jsonContent = $serializer->serialize(
            $notification,
            'json',
            ['groups' => ['notification_basic_view', 'notification_user_view']]
        );

        return JsonResponse::fromJsonString($jsonContent);
    }

    #[Route('/api/notification/{id}', name: 'app_notification_delete', methods: ['DELETE'])]
    public function delete(Request $request, Notification $notification, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $notification->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($notification);
            $entityManager->flush();
        }

        return new Response(200);
    }

    #[Route('/api/notification/users/search', name: 'app_notification_getUsersForNotification', methods: ['GET'])]
    public function getUsersForNotification(NotificationRepository $notificationRepository, UserRepository $userRepository, Request $request): Response
    {
        $query = $request->query->get('query', '');
        $limit = $request->query->getInt('limit', 10); // Default limit is 10 if not provided
        $users = $userRepository->findUsersForNotifications($query, $limit);

        return $this->json(
            [
                'success' => true,
                'users' => $users,
            ]
        );
    }

    #[Route('/api/notification/tags/search', name: 'app_notification_getTagsForNotification', methods: ['GET'])]
    public function getTagsForNotification(NotificationTagRepository $notificationTagRepository, Request $request): Response
    {
        $query = $request->query->get('query', '');
        $limit = $request->query->getInt('limit', 10); // Default limit is 10 if not provided
        $tags = $notificationTagRepository->findTagsForNotifications($query, $limit);

        return $this->json(
            [
                'success' => true,
                'tags' => $tags,
            ]
        );
    }

    #[Route('/api/notifications', name: 'app_notifications_by_category', methods: ['GET'])]
    public function getNotificationsByCategory(Request $request, NotificationRepository $notificationRepository): Response
    {
        $category = $request->query->get('category');
        $notifications = $notificationRepository->findByCategory($this->getUser(), $category);
        return $this->json(['notifications' => $notifications]);
    }
}