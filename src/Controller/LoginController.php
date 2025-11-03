<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        if($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('login/index.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/forgotPassword', name: 'forgot_password')]
    public function forgotPassword(): Response
    {
        return $this->render('login/forgotPassword.html.twig', [
            'controller_name' => 'PasswordForgotController',
        ]);
    }
    #[Route('/login/first', name: 'first_login')]
    public function firstLogin(): Response
    {
        return $this->render('login/firstLogin.html.twig', [
            'controller_name' => 'FirstLoginController',
        ]);
    }

    #[Route('/api/first-login/change-password', name: 'api_change_first_password', methods: ['POST'])]
    public function changeFirstPassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!$passwordHasher->isPasswordValid($user, $data['oldPassword'])) {
            return new JsonResponse(['error' => 'Invalid old password'], 400);
        }

        if ($data['newPassword'] !== $data['confirmPassword']) {
            return new JsonResponse(['error' => 'Passwords do not match'], 400);
        }

        $hashedPassword = $passwordHasher->hashPassword($user, $data['newPassword']);
        $user->setPassword($hashedPassword);
        $user->setFirstLogin(false);

        $entityManager->persist($user);
        $entityManager->flush();

        // Log out the user
        $this->container->get('security.token_storage')->setToken(null);
        $request->getSession()->invalidate();

        return new JsonResponse(['message' => 'Password updated successfully']);
    }
}
