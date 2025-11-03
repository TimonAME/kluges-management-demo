<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Crypto\DkimSigner;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class EMailController extends AbstractController
{
    /**
     * @throws TransportExceptionInterface
     */
    public function newAccount(string $first_name, string $last_name, string $email, string $password, MailerInterface $mailer): Response
    {
        $emailToSend = (new TemplatedEmail()) //create the mail
            ->from('noreply@klugesmanagement.at')
            ->subject('Willkommen ' . $first_name . ' ' . $last_name . '!')
            ->htmlTemplate('email/newAccount.html.twig') //the template for the mail
            ->to($email) //the address the mail shall be sent to
            ->context([
                'expiration_date' => new \DateTimeImmutable('+7 days'), //expiration date for the mail
                'username' => $first_name . ' ' . $last_name, //the full name of the person the mail is being sent to
                'password' => $password, //hand the password to the mail
            ])
        ;
        $mailer->send($emailToSend);
        return new Response('Account Creation Mail sent');
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function dataDeletion(string $first_name, string $last_name, string $email, string $deletionHash, MailerInterface $mailer): Response
    {
        $emailToSend = (new TemplatedEmail())
            ->from('noreply@klugesmanagement.at')
            ->subject('Account-Löschung')
            ->htmlTemplate('email/dataDeletion.html.twig')
            ->to($email)
            ->context([
                'expiration_date' => new \DateTimeImmutable('+7 days'),
                'username' => $first_name . ' ' . $last_name,
                'deletionHash' => $deletionHash
            ])
        ;
        $mailer->send($emailToSend);
        return new Response('Data Deletion Mail sent');
    }
/*
    #[Route('/email/datadeletion', name: 'app_email_datadeletion_preview', methods: ['GET'])]
    public function previewDataDeletionEmail(): Response
    {
        return $this->render('email/dataDeletion.html.twig', [
            'expiration_date' => new \DateTimeImmutable('+7 days'),
            'username' => 'Test User',
            'deletionHash' => 'sample-deletion-hash-123'
        ]);
    }
    #[Route('/email/newaccount', name: 'app_email_newaccount_preview', methods: ['GET'])]
    public function previewNewAccountEmail(): Response
    {
        return $this->render('email/newAccount.html.twig', [
            'expiration_date' => new \DateTimeImmutable('+7 days'),
            'username' => 'Test User',
            'password' => 'sample-password-123',
            'email' => ['to' => [['address' => 'test@example.com']]]
        ]);
    }
*/
}
