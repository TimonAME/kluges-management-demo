<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class SecurityController extends AbstractController
{
    #[Route('/quick-login', name: 'app_quick_login')]
    public function quickLogin(EntityManagerInterface $entityManager): Response
    {
        // Find admin user
        $user = $entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'anna.gruber@example.com']);

        if (!$user) {
            throw $this->createNotFoundException('Admin user not found');
        }

        // Manual login
        $token = new UsernamePasswordToken(
            $user,
            'main', // firewall name
            $user->getRoles()
        );

        $this->container->get('security.token_storage')->setToken($token);

        // Redirect to dashboard
        return $this->redirectToRoute('app_dashboard');
    }
}