<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Controller\EMailController;
use App\DTO\WorkingHours;
use App\Entity\Subject;
use App\Entity\Todo;
use App\Entity\User;
use App\Entity\UserTodo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use function Webmozart\Assert\Tests\StaticAnalysis\null;

class UserProcessor implements ProcessorInterface
{

    private Security $security;
    private EntityManagerInterface $entityManager;
    private MailerInterface $mailer;
    private EMailController $eMailController;

    public function __construct(Security $security, EntityManagerInterface $entityManager, EMailController $eMailController, MailerInterface $mailer)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->eMailController = $eMailController;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User|null
    {
        $route = $context['request']?->attributes->get('_route'); //get the type of request for the switch case
        $decodedContent = json_decode($context['request']?->getContent(), true); //get the request body data
        $user = $this->security->getUser(); //get the current user
        $userRepository = $this->entityManager->getRepository(User::class);
        $subjectRepository = $this->entityManager->getRepository(Subject::class);
        if (!$user instanceof User) { //check if the user is authenticated
            throw new \LogicException('No authenticated user found.');
        }

        switch ($route) {

            case 'updateWorkingHours':
                $chosenUser = $userRepository->find($uriVariables['id']);
                $chosenUser->setWorkingHours(
                    new WorkingHours(
                        $decodedContent['workingHours']['template'],
                        $decodedContent['workingHours']['individual']
                    )
                );
                break;
            case 'changeProfilePicture':

                //delete if exists
                if (null !== $user->getPfpPath()) {
                    $oldFile = $user->getPfpPath();
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }

                // Extract base64 data
                $base64Data = explode(',', $decodedContent['base64']);
                if (!isset($base64Data[1])) {
                    throw new \InvalidArgumentException('Invalid base64 format');
                }

                // Decode base64 data
                $decodedImage = base64_decode($base64Data[1]);
                if ($decodedImage === false || filesize($decodedImage) > 1000000) {
                    throw new \RuntimeException('Failed to decode image');
                }

                // Generate unique filename
                $fileName = "./uploads/pfp/" . uniqid() . '.png';
                file_put_contents($fileName, $decodedImage);

                // Update path to image
                $user->setPfpPath($fileName);
                break;
            case 'addUserSubject':
                $chosenUser = $userRepository->find($uriVariables['id']);
                $subject = $subjectRepository->find($decodedContent['subjectId']);
                if ($subject) {
                   echo $subject->getName();
                    $chosenUser->addSubjectsRelatedToUser($subject);
                    $this->entityManager->persist($chosenUser);
                }
                break;
            case 'removeUserSubject':
                $chosenUser = $userRepository->find($uriVariables['id']);
                $subject = $subjectRepository->find($decodedContent['subjectId']);

                if ($subject) {
                    $chosenUser->removeSubjectsRelatedToUser($subject);
                    $this->entityManager->persist($chosenUser);
                }
                break;
            case 'deleteUserMail':
                echo "check";
                $id = $uriVariables['id'];
                $userToDelete = $userRepository->find($id);
                $this->eMailController->dataDeletion($userToDelete->getFirstName(), $userToDelete->getLastName(), $userToDelete->getEmail(), $this->mailer);
        }
        $this->entityManager->flush();
        return $user;
    }
}