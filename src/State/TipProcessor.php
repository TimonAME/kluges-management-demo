<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Tip;
use App\Entity\TipCategory;
use App\Entity\TipUser;
use App\Entity\Todo;
use App\Entity\User;
use App\Entity\UserTodo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class TipProcessor implements ProcessorInterface
{
    private Security $security;
    private EntityManagerInterface $entityManager;

    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Tip|null
    {
        $route = $context['request']?->attributes->get('_route'); //get the type of request for the switch case
        $decodedContent = json_decode($context['request']?->getContent(), true); //get the request body data
        $currentUser = $this->security->getUser(); //get the current user
        $userRepository = $this->entityManager->getRepository(User::class);
        $tipRepository = $this->entityManager->getRepository(Tip::class);
        $tipCategoryRepository = $this->entityManager->getRepository(TipCategory::class);
        $tipUserRepository = $this->entityManager->getRepository(TipUser::class);
        $tip = null;
        if (!$currentUser instanceof UserInterface) { //check if the user is authenticated
            throw new \LogicException('No authenticated user found.');
        }

        switch ($route) {
            case 'createTip':
                $tip = new Tip();
                $tip->setCreator($currentUser);
                $tip->setTipCategory($tipCategoryRepository->find($decodedContent['tipCategory']['id']));
                $tip->setTitle($decodedContent['title']);
                $tip->setMessage($decodedContent['message']);
                $now = new \DateTime('now');
                $now->format('Y-m-d');
                $tip->setCreationDate($now);
                $users = $userRepository->findAll();
                foreach ($users as $user) {
                    $role = $user->getRoles()[0] ?? '';

                    if ($role === 'ROLE_STUDENT' || $role === 'ROLE_GUARDIAN') {
                        continue; // Skip this user
                    }

                    $tipUser = new TipUser();
                    $tipUser->setUser($user);
                    $tipUser->setTip($tip);
                    $tipUser->setReadAt(null);

                    $this->entityManager->persist($tipUser);
                }
                $this->entityManager->persist($tip);
                break;
            case 'checkTip':
                $tip = $tipRepository->find($uriVariables['id']);
                $tipUser = $tipUserRepository->find($tipUserRepository->getByTipAndUser($currentUser, $tip));
                $now = new \DateTime('now');
                echo $now->format('Y-m-d H:i:s') . "\n";
                $tipUser->setReadAt($now);
        }
        $this->entityManager->flush();
        return $tip;
    }
}