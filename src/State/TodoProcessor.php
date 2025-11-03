<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Todo;
use App\Entity\User;
use App\Entity\UserTodo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class TodoProcessor implements ProcessorInterface
{
    private Security $security;
    private EntityManagerInterface $entityManager;

    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Todo|null
    {
        $route = $context['request']?->attributes->get('_route'); //get the type of request for the switch case
        $decodedContent = json_decode($context['request']?->getContent(), true); //get the request body data
        $user = $this->security->getUser(); //get the current user
        $userRepository = $this->entityManager->getRepository(User::class);
        $todoRepository = $this->entityManager->getRepository(Todo::class);
        $userTodoRepository = $this->entityManager->getRepository(UserTodo::class);
        $todo = null;
        if (!$user instanceof UserInterface) { //check if the user is authenticated
            throw new \LogicException('No authenticated user found.');
        }

        switch ($route) {
            case 'createTodo':
                $todo = new Todo();
                $todo->setCreator($user);
                $todo->setDescription($decodedContent['description']);
                $todo->setTitle($decodedContent['title']);

                $todo->setDueDate($decodedContent['dueDate'] !== null ? new \DateTime($decodedContent['dueDate']) : null);


                foreach($decodedContent['userTodos'] as $assignUser) {
                    $userTodo = new UserTodo();
                    $userToAdd = $userRepository->find($assignUser['user']['id']);
                    $userTodo->setChecked(false);
                    $userToAdd->addUserTodo($userTodo);
                    $todo->addUserTodo($userTodo);
                    $this->entityManager->persist($userTodo);
                }
                $this->entityManager->persist($todo);
                break;

            //update the todo
            case 'updateTodo':
                $todo = $todoRepository->find($uriVariables['id']);
                $todo->setDescription($decodedContent['description'] ?? $todo->getDescription());
                $todo->setTitle($decodedContent['title'] ?? $todo->getTitle());
                if (isset($decodedContent['dueDate'])) {
                    $todo->setDueDate($decodedContent['dueDate'] !== null ? new \DateTime($decodedContent['dueDate']) : null);
                }
                if (isset($decodedContent['userTodos'])) {
                    foreach ($decodedContent['userTodos'] as $assignUser) {
                        $userToAdd = $userRepository->find($assignUser['user']['id']);
                        if ($userToAdd !== null) {
                            if (!$userTodoRepository->getByTodoAndUser($userToAdd, $todo)) {
                                $userTodo = new UserTodo();
                                $userTodo->setChecked(false);
                                $userToAdd->addUserTodo($userTodo);
                                $todo->addUserTodo($userTodo);
                                $this->entityManager->persist($userTodo);
                            }
                        }
                    }
                }
                break;

            //check or uncheck the todo for the user
            case 'checkTodo':
                $todo = $todoRepository->find($uriVariables['id']);
                $userTodo = $userTodoRepository->find($userTodoRepository->getByTodoAndUser($user, $todo));
                $userTodo->setChecked(!$userTodo->isChecked());
                break;

            case 'deleteUserTodo':
                $todo = $todoRepository->find($uriVariables['id']);
                $chosenUser = $userRepository->find($decodedContent['uid']);
                $userTodo = $userTodoRepository->find($userTodoRepository->getByTodoAndUser($chosenUser, $todo));
                $todo->removeUserTodo($userTodo);
                break;
        }
        $this->entityManager->flush();
        return $todo;
    }
}