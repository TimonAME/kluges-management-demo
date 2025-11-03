<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\AppointmentRepository;
use App\Repository\ConversationRepository;
use App\Repository\HonorariumRepository;
use App\Repository\IllnessReportRepository;
use App\Repository\NotificationRepository;
use App\Repository\TodoRepository;
use App\Repository\UserRepository;
use App\Repository\UserTodoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;

use function PHPUnit\Framework\assertTrue;

class UserController extends AbstractController
{
    #[Route('/benutzerliste/{role?}', name: 'app_user_list', methods: ['GET'])]
    public function listUsers(Request $request, UserRepository $userRepository, ?string $role = ''): Response
    {
        $searchTerm = $request->query->get('search', '');
        $sortBy = $request->query->get('sortBy', 'id');
        $sortDirection = $request->query->get('sortDirection', 'ASC');
        $limit = (int) $request->query->get('limit', 10);
        $offset = (int) $request->query->get('offset', 0);
        $users = $userRepository->searchUsers($searchTerm, $role ?? '', $sortBy, $sortDirection, $limit, $offset);

        return $this->render('student_list/index.html.twig', [
            'users' => $users,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    // Method to send the deletion mail after pressing the button

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('user/sendDelete/{id<\d+>}', name: 'app_user_delete_mail', methods: ['POST'])]
    public function sendDeletionMail(int $id, EntityManagerInterface $entityManager, EMailController $eMailController, MailerInterface $mailer): Response
    {

        if (!$this->isGranted('ROLE_OFFICE')) {
            throw $this->createAccessDeniedException();
        }

        $userToDelete = $entityManager->getRepository(User::class)->find($id);

        // Generate a secure random token
        $deletionHash = bin2hex(random_bytes(16));

        // Save the token to the user
        $userToDelete->setDeletionRequestToken($deletionHash);
        $entityManager->flush();

        $userToDelete = $entityManager->getRepository(User::class)->find($id);
        $eMailController->dataDeletion($userToDelete->getFirstName(), $userToDelete->getLastName(), $userToDelete->getEmail(), $deletionHash, $mailer);
        return $this->redirectToRoute('app_user_list');
    }


    // This method is used to delete a user and all related data from the database and redirect to app_user_index
    #[Route('/delete/{token}', name: 'app_user_confirm_deletion', methods: ['GET'])]
    public function confirmDeletion(string $token, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $userRepository->findOneBy(['deletionRequestToken' => $token]);

        if (!$user) {
            return $this->redirectToRoute('app_login', ['deletion_success' => 'false']);
        }

        // Process user deletion
        $user->setIsDeleted(true);
        $user->setEmail('anonymized_' . $user->getId() . '@example.com');
        $user->setFirstName('Anonymized');
        $user->setLastName('User');
        $user->setPassword('');
        $user->setBirthday(new \DateTime("1000-01-01+01:00"));
        $user->setLearningLevel("");
        $user->setPrivateLocation(null);
        $user->setDeletionRequestToken(null);

        $entityManager->flush();

        return $this->redirectToRoute('app_login', ['deletion_success' => 'true']);
    }

    // API endpoint for finding users
    // Search via subject explanation:
    // If the subjects query parameter is set, the method will search for users that teach the subjects specified in the query parameter
    // The subjects query parameter should be a comma-separated list of subject names



    #[Route('/user', name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/user/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EMailController $eMailController): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            $eMailController->newAccount(
                $user->getFirstName(),
                $user->getLastName(),
                $user->getEmail(),
                $user->getPassword(),
            );

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/user/{id<\d+>}/{category?}', name: 'app_user_show', methods: ['GET'])]
    public function userProfile(int $id, UserRepository $userRepository, ?string $category = null): Response
    {
        $user = $userRepository->findByIdForUserProfile($id);

        return $this->render('user/user.html.twig', [
            'user' => $user,
            'category' => $category,
        ]);
    }

    #[Route('/user/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    /*#[Route('/user/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }*/

    #[Route('/api/user/me', name: 'api_user_me', methods: ['GET'])]
    public function getCurrentUser(UserRepository $userRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $userId = $user->getId();
        $userData = $userRepository->findCurrentUser($userId);

        return new JsonResponse($userData);
    }

    #[Route('/user/role/{id}', name: 'api_user_role_get', methods: ['GET'])]
    public function getUserRole(User $user): JsonResponse
    {
        if (!$this->isGranted('ROLE_OFFICE')) {
            return new JsonResponse(['error' => 'Insufficient permissions'], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse(['role' => $user->getRoles()[0]]);
    }

    #[Route("/user/role/{id}", name: "api_user_role", methods: ["POST"])]
    public function changeRole(Request $request, User $target, EntityManagerInterface $entityManager): JsonResponse
    {
        assertTrue($this->getUser() instanceof User);
        
        $roles = ['ROLE_STUDENT', 'ROLE_GUARDIAN',  'ROLE_TEACHER',  'ROLE_MARKETING','ROLE_OFFICE', 'ROLE_LOCATION_MANAGEMENT', 'ROLE_MANAGEMENT'];
        
        // Lesen des Parameters aus dem JSON-Request-Body
        $data = json_decode($request->getContent(), true);
        $role = $data['role'] ?? null;
        
        if (!$role || !in_array($role, $roles)) {
            return new JsonResponse(['error' => 'Invalid role'], Response::HTTP_BAD_REQUEST);
        }
        
        if (array_search($role, $roles) > array_search($this->getUser()->getRoles()[0], $roles)) {
            return new JsonResponse(['error' => 'Insufficient permissions'], Response::HTTP_FORBIDDEN);
        } else {
            $target->setRoles([$role]);
            $entityManager->flush();
            return new JsonResponse(['message' => 'Role changed successfully']);
        }
    }

    #[Route("/user/registration", name: "api_user_registration", methods: ["GET"])]
    public function registerUser(): Response
    {
        return $this->render('user/new.html.twig');
    }
}
