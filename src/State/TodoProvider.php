<?php
namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Todo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use function Webmozart\Assert\Tests\StaticAnalysis\null;

class TodoProvider implements ProviderInterface
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|null|object
    {
        $route = $context['request']?->attributes->get('_route');
        $currentUser = $this->security->getUser();
        $todoRepository = $this->entityManager->getRepository(Todo::class);

        switch ($route) {
            case 'readAllForUser':
                return $todoRepository->findForUser($currentUser);
                break;
            case 'searchTodos':
                return $todoRepository->getTodosByQuery($currentUser, $context['request']->query->get('query'));
        }
        return new ArrayCollection();
        // Custom query to get the required data
    }
}
