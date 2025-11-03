<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use App\Repository\SubjectRepository;
use App\State\SubjectProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


#[ORM\Entity(repositoryClass: SubjectRepository::class)]
#[ApiResource(
    description: "Ein Subject.",
    operations: [
        new GetCollection(
            uriTemplate: '/subjects',
            normalizationContext: ['groups' => ['subject:read:all']]
        ),
        new Post(
            uriTemplate: "/subjects",
            description: "Create a new subject",
            normalizationContext: ['groups' => ['subject:read:all']],
            name: "createSubject",
        ),
        new Get(
            uriTemplate: '/subjects/{id}',
            normalizationContext: ['groups' => ['subject:read:all']]
        ),
        new Delete(
            uriTemplate: '/subjects/{id}'
        ),
        new Patch(
            uriTemplate: '/subjects/{id}',
            normalizationContext: ['groups' => ['subject:read:all']],
            denormalizationContext: ['groups' => ['subject:update']]
        ),
        new GetCollection(
            uriTemplate: '/subject/tutoring/teachers-by-subject',
            normalizationContext: ['groups' => ['user:basic', 'user:work']],
            security: "is_granted('SUBJECT_APPOINTMENT_PLANNING', object)",
            name: 'getTeachersBySubjectName',
            provider: SubjectProvider::class
        ),
        new GetCollection(
            uriTemplate: '/subject/student/search',
            normalizationContext: ['groups' => ['user:basic']],
            security: "is_granted('SUBJECT_APPOINTMENT_PLANNING', object)",
            name: 'searchStudentsBySubject',
            provider: SubjectProvider::class
        ),
    ],
    formats: [
        'json'
    ]
)]
class Subject
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['subject:read:all'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['subject:read:all', 'subject:create', 'subject:update'])]
    private ?string $name = null;

    /**
     * @var Collection<int, Exam>
     */
    #[ORM\OneToMany(targetEntity: Exam::class, mappedBy: 'subject')]
    private Collection $exams;



    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * Color hex code for displaying the subject in the calender and other places
     */
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['subject:read:all', 'subject:create', 'subject:update'])]
    private ?string $color_hex_code = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'subjects_related_to_user')]
    private Collection $users_related_to_subject;

    public function __construct()
    {
        $this->exams = new ArrayCollection();
        $this->users_related_to_subject = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Exam>
     */
    public function getExams(): Collection
    {
        return $this->exams;
    }

    public function addExam(Exam $exam): static
    {
        if (!$this->exams->contains($exam)) {
            $this->exams->add($exam);
            $exam->setSubject($this);
        }

        return $this;
    }

    public function removeExam(Exam $exam): static
    {
        if ($this->exams->removeElement($exam)) {
            // set the owning side to null (unless already changed)
            if ($exam->getSubject() === $this) {
                $exam->setSubject(null);
            }
        }

        return $this;
    }

    public function getColorHexCode(): ?string
    {
        return $this->color_hex_code;
    }

    public function setColorHexCode(?string $color_hex_code): static
    {
        $this->color_hex_code = $color_hex_code;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsersRelatedToSubject(): Collection
    {
        return $this->users_related_to_subject;
    }

    public function addUsersRelatedToSubject(User $usersRelatedToSubject): static
    {
        if (!$this->users_related_to_subject->contains($usersRelatedToSubject)) {
            $this->users_related_to_subject->add($usersRelatedToSubject);
            $usersRelatedToSubject->addSubjectsRelatedToUser($this);
        }

        return $this;
    }

    public function removeUsersRelatedToSubject(User $usersRelatedToSubject): static
    {
        if ($this->users_related_to_subject->removeElement($usersRelatedToSubject)) {
            $usersRelatedToSubject->removeSubjectsRelatedToUser($this);
        }

        return $this;
    }
}
