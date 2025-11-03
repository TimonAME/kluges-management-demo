<?php
namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Tip;
use App\Entity\Todo;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use function PHPUnit\Framework\isNull;
use function Webmozart\Assert\Tests\StaticAnalysis\null;

class TipProvider implements ProviderInterface
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
        $tipRepository = $this->entityManager->getRepository(Tip::class);

        switch ($route) {
            case 'getAllTipsForMe':
                return $tipRepository->getTipAndReadDate($currentUser);
        }
        // Custom query to get the required data
        return null; // Example: fetch sorted by due date
    }
}
