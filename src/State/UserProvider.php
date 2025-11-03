<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Conversation;
use App\Entity\Notification;
use App\Entity\Subject;
use App\Entity\Tip;
use App\Entity\Todo;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use function PHPUnit\Framework\isNull;
use function Webmozart\Assert\Tests\StaticAnalysis\null;

class UserProvider implements ProviderInterface
{
    private EntityManagerInterface $entityManager;
    private Security $security;
    private NormalizerInterface $normalizer;

    public function __construct(EntityManagerInterface $entityManager, Security $security, NormalizerInterface $normalizer)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->normalizer = $normalizer;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|null|object
    {
        $route = $context['request']?->attributes->get('_route');
        $currentUser = $this->security->getUser();
        $userRepository = $this->entityManager->getRepository(User::class);
        $conversationRepository = $this->entityManager->getRepository(Conversation::class);
        $subjectRepository = $this->entityManager->getRepository(Subject::class);

        $limit = $context['request']->query->get('limit');
        switch ($route) {
            case 'userSearchForTodo':
                if (!$limit) {
                    return $userRepository->searchUsersForTodoByQuery($context['request']->query->get('query'));
                }
                return $userRepository->searchUsersForTodoByQuery($context['request']->query->get('query'), $limit);
            case 'notificationsForUser':
                $userId = $uriVariables['id'];
                $user = $userRepository->find($userId);
                $notificationRepository = $this->entityManager->getRepository(Notification::class);
                if (isset($context["filters"]["category"])) {
                    switch ($context["filters"]["category"]) {
                        case "read":
                            return $notificationRepository->findNotificationsForUser($user, true);
                        case "unread":
                            return $notificationRepository->findNotificationsForUser($user, false);
                        default:
                            return $notificationRepository->findNotificationsForUser($user);
                    }
                } else {
                    return $notificationRepository->findNotificationsForUser($user);
                }
            case 'todosForUser':
                $userId = $uriVariables['id'];
                $user = $userRepository->find($userId);
                $todoRepository = $this->entityManager->getRepository(Todo::class);
                if (isset($context["filters"]["category"])) {
                    switch ($context["filters"]["category"]) {
                        case "done":
                            return $todoRepository->findTodosForUser($user, true);
                        case "undone":
                            return $todoRepository->findTodosForUser($user, false);
                        default:
                            return $todoRepository->findTodosForUser($user);
                    }
                } else {
                    return $todoRepository->findTodosForUser($user);
                }
            case 'getSubjectsForUser':
                $userId = $uriVariables['id'];
                $user = $userRepository->find($userId);
                if ($user) {
                    // Get the collection of subjects directly from the user entity
                    $subjects = $user->getSubjectsRelatedToUser()->toArray();

                    return $subjects;
                }
                return [];
            case 'dropdownUsers':
                $searchTerm = $context['request']?->query->get('searchTerm', '');
                $users = $conversationRepository->findUsersForDropdown($currentUser->getId(), $searchTerm);
                return $this->normalizer->normalize($users, null, ['groups' => ['user:basic']]);
            case 'apiListUsers':
                $searchTerm = $context['request']->query->get('search', '');
                $role = $context['request']->query->get('role', '');
                $sortBy = $context['request']->query->get('sortBy', 'id');
                $sortDirection = $context['request']->query->get('sortDirection', 'ASC');
                $limit = (int) $context['request']->query->get('limit', 10);
                $offset = (int) $context['request']->query->get('offset', 0);

                if ($context['request']->query->get('subjects')) {
                    $subjectNames = explode(',', $context['request']->query->get('subjects', ''));
                    return $userRepository->findTeachersBySubjects($subjectNames, $searchTerm, $sortBy, $sortDirection, $limit, $offset);
                } else {
                    return $userRepository->searchUsers($searchTerm, $role, $sortBy, $sortDirection, $limit, $offset);
                }

            case 'getTipsForUser':
                $userId = $uriVariables['id'];
                $user = $userRepository->find($userId);
                    $tipRepository = $this->entityManager->getRepository(Tip::class);
                    if (isset($context["filters"]["category"])) {
                        switch ($context["filters"]["category"]) {
                            case "read":
                                return $tipRepository->findTipsForUser($user, true);
                            case "unread":
                                return $tipRepository->findTipsForUser($user, false);
                            default:
                                return $tipRepository->findTipsForUser($user);
                        }
                    } else {
                        return $tipRepository->findTipsForUser($user);
                }
                return [];
        }
        // Custom query to get the required data
        return null; // Example: fetch sorted by due date
    }
}
