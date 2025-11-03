<?php

namespace App\Controller;

use App\Entity\Location;
use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager, EMailController $eMailController): Response
    {
        return $this->render('user/new.html.twig');
    }

    #[Route('/api/register', name: 'api_app_register', methods: ['POST'])]
    public function register_api(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager, EMailController $eMailController, MailerInterface $mailer): Response
    {
        $data = json_decode($request->getContent(), true);

        // Generate a random password
        $password = bin2hex(random_bytes(8));

        // Create and persist the main user
        $user = new User();
        $user->setFirstName($data['firstName']);
        $user->setLastName($data['lastName']);
        $user->setEmail($data['email']);
        $user->setBirthday(new \DateTime($data['birthDate']));
        $user->setDateCreated(new \DateTime());
        $user->setRoles([$data['role']]);
        $user->setGender(1);
        $user->setPassword(
            $userPasswordHasher->hashPassword(
                $user,
                $password
            )
        );

        // Set private location if provided
        if (isset($data['address'])) {
            $location = new Location();
            $location->setStreet($data['address']['street']);
            $location->setPostalCode($data['address']['postalCode']);
            $location->setCity($data['address']['city']);
            $entityManager->persist($location);
            $user->setPrivateLocation($location);
        }

        $entityManager->persist($user);

        // If the user is a student, create and persist the guardian user
        if ($data['role'] === 'ROLE_STUDENT' && isset($data['guardian'])) {
            $guardianPassword = bin2hex(random_bytes(8));
            $guardian = new User();
            $guardian->setFirstName($data['guardian']['firstName']);
            $guardian->setLastName($data['guardian']['lastName']);
            $guardian->setEmail($data['guardian']['email']);
            $guardian->setBirthday(new \DateTime($data['guardian']['birthDate']));
            $guardian->setRoles(['ROLE_GUARDIAN']);
            $guardian->setPassword(
                $userPasswordHasher->hashPassword(
                    $guardian,
                    $guardianPassword
                )
            );

            // Set guardian private location if provided
            if (isset($data['guardian']['address'])) {
                $guardianLocation = new Location();
                $guardianLocation->setStreet($data['guardian']['address']['street']);
                $guardianLocation->setPostalCode($data['guardian']['address']['postalCode']);
                $guardianLocation->setCity($data['guardian']['address']['city']);
                $entityManager->persist($guardianLocation);
                $guardian->setPrivateLocation($guardianLocation);
            }

            $entityManager->persist($guardian);

            // Set the guardian relationship
            $user->setGuardian($guardian);
        }

        $entityManager->flush();

        echo "Here!";

        // Send email notification
        $eMailController->newAccount(
            $user->getFirstName(),
            $user->getLastName(),
            $user->getEmail(),
            $password,
            $mailer
        );

        // Log in the user
        return $security->login($user, 'form_login', 'main');
    }
}
