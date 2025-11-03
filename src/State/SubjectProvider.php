<?php
namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Subject;
use App\Repository\SubjectRepository;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class SubjectProvider implements ProviderInterface
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    /**
     * @throws \Exception
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|null|object
    {
        $route = $context['request']?->attributes->get('_route');
        $currentUser = $this->security->getUser();
        $subjectRepository = $this->entityManager->getRepository(Subject::class);
        $userRepository = $this->entityManager->getRepository('App\Entity\User');

        switch ($route) {
            case "getSubjectsForUser":
                return $subjectRepository->findSubjectsForUser($currentUser->getId());

            case "getTeachersBySubjectName":
                $subjectName = $context['request']->query->get('subject', '');
                if (empty($subjectName)) {
                    return [];
                }
                return $subjectRepository->findTeachersBySubjectName($subjectName);

            case "searchStudentsBySubject":
                $searchTerm = $context['request']->query->get('search', '');
                $subjectName = $context['request']->query->get('subject', null);

                if (empty($searchTerm)) {
                    return [];
                }

                return $subjectRepository->findStudentsBySearchAndSubject($searchTerm, $subjectName);
        }
        return new ArrayCollection();
    }
}
