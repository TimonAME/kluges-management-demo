<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\OpenApi;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\DTO\WorkingHours;
use App\Repository\UserRepository;
use App\State\SubjectProvider;
use App\State\TipProvider;
use App\State\UserProcessor;
use App\State\UserProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use PHPUnit\Util\Json;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[ApiResource(
    description: "Ein User.",
    operations: [
        new Get(
            uriTemplate: "/users/{id}",
            normalizationContext: ['groups' => ['user:basic']],
            name: 'getUser',
        ),
        new GetCollection(
            uriTemplate: "/user/search/todo",
            normalizationContext: ['groups' => ['users:search:todo']],
            name: 'userSearchForTodo',
            provider: UserProvider::class
        ),
        new Patch(
            uriTemplate: "/user/work/{id}",
            normalizationContext: ['groups' => ['user:basic', 'user:work']],
            denormalizationContext: ['groups' => ['user:work']],
            security: "is_granted('USER_WORK', object)",
            name: 'updateWorkingHours',
            processor: UserProcessor::class
        ),
        new Get(
            uriTemplate: "/user/work/{id}",
            normalizationContext: ['groups' => ['user:basic', 'user:work']],
            security: "is_granted('USER_WORK_VIEW', object)",
            name: 'getWorkingHours'
        ),
        new GetCollection(
            uriTemplate: "/user/chat/dropdown-users",
            paginationEnabled: false,
            normalizationContext: ['groups' => ['user:basic']],
            name: 'dropdownUsers',
            provider: UserProvider::class
        ),
        new GetCollection(
            uriTemplate: "/users",
            paginationEnabled: false,
            normalizationContext: ['groups' => ['user:basic']],
            name: 'apiListUsers',
            provider: UserProvider::class
        ),
        new GetCollection(
            uriTemplate: "user/{id}/notifications",
            name: "notificationsForUser",
            normalizationContext: ['groups' => ['user:read:notifications', 'notification_basic_view']],
            paginationEnabled: false,
            provider: UserProvider::class
        ),
        new GetCollection(
            uriTemplate: "user/{id}/todos",
            name: "todosForUser",
            normalizationContext: ['groups' => ['user:read:todos', "todo:read:basic"]],
            paginationEnabled: false,
            provider: UserProvider::class
        ),
        new Put(
            uriTemplate: "/user/{id}/pfp",
            denormalizationContext: ['groups' => ['user:pfp']],
            normalizationContext: ['groups' => ['user:pfp']],
            name: 'changeProfilePicture',
            processor: UserProcessor::class,
            provider: UserProvider::class
        ),
        new Get(
            uriTemplate: "user/{id}/pfp",
            name: "getPfpForUser",
            normalizationContext: ['groups' => ['user:pfp']],
        ),
        new Get(
            uriTemplate: "user/{id}/advanced",
            name: "getUserAdvanced",
            normalizationContext: ['groups' => ['user:read:advanced', "user:basic"]]
        ),
        new Patch(
            uriTemplate: "user/{id}/advanced",
            name: "updateUserAdvanced",
            normalizationContext: ['groups' => ['user:read:advanced', "user:basic"]],
            denormalizationContext: ['groups' => ['user:read:advanced', "user:basic"]]
        ),
        new GetCollection(
            uriTemplate: "user/{id}/subjects",
            name: "getSubjectsForUser",
            normalizationContext: ['groups' => ['subject:read:all']],
            paginationEnabled: false,
            provider: UserProvider::class,
        ),
        new Patch(
            uriTemplate: "user/{id}/subjects",
            name: "addUserSubject",
            processor: UserProcessor::class,
            denormalizationContext: ['groups' => ['subject:toggle']],
            normalizationContext: ['groups' => ['subject:read:all']],
        ),
        new Delete(
            uriTemplate: "user/{id}/subjects",
            name: "removeUserSubject",
            processor: UserProcessor::class,
            denormalizationContext: ['groups' => ['subject:toggle']],
            normalizationContext: ['groups' => ['subject:read:all']],
        ),
        new Get(
            uriTemplate: "user/{id}/tips",
            name: "getTipsForUser",
            normalizationContext: ['groups' => ['tip:read:user']],
            provider: UserProvider::class,
            paginationEnabled: false,
        )
    ],
    formats: [
        'json'
    ],
    outputFormats: [
        'json'
    ],
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:basic', 'notification_basic_view', 'todo:create', 'todo:read:all', 'todo:read:detail', 'todo:delete:UserTodo', 'appointment:create', 'users:search:todo', 'appointment:view:detail', 'appointment:delete:user', 'tip:readAt:all', 'conversation:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(['user:basic', 'todo:read:all', 'todo:read:detail', 'users:search:todo', 'appointment:view:detail', 'tip:readAt:all'])]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Ignore]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:basic', 'notification_basic_view', 'todo:read:all', 'todo:read:detail', 'users:search:todo', 'appointment:view:detail', 'tip:readAt:all'])]
    private ?string $first_name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:basic', 'notification_basic_view', 'todo:read:all', 'todo:read:detail', 'users:search:todo', 'appointment:view:detail', 'tip:readAt:all'])]
    private ?string $last_name = null;

    /**
     * @var string $learning_level Short description of the user's learning level (school type, grade, etc.)
     */
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read:advanced'])]
    private ?string $learning_level = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'wards')]
    #[Groups(['user:read:advanced'])]
    private ?self $guardian = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'guardian')]
    #[Groups(['user:read:advanced'])]
    private Collection $wards;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[Groups(['user:read:advanced'])]
    private ?Location $private_location = null;

    /**
     * @var Collection<int, Exam>
     */
    #[ORM\OneToMany(targetEntity: Exam::class, mappedBy: 'user_taking_exam')]
    private Collection $exams;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\ManyToMany(targetEntity: Notification::class, mappedBy: 'users')]
    #[Groups(['user:read:notifications', 'notification_basic_view'])]
    private Collection $notifications;

    /**
     * @var Collection<int, Todo>
     */
    #[ORM\OneToMany(targetEntity: Todo::class, mappedBy: 'creator')]
    #[Groups(["user:read:todos"])]
    private Collection $createdTodos;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['user:read:advanced'])]
    private ?\DateTimeInterface $birthday = null;

    #[ORM\ManyToOne(inversedBy: 'employees_at_location')]
    private ?Location $company_location = null;

    /**
     * @var Collection<int, Subject>
     * Subjects that are either taught by the user or that the user is taking
     */
    #[ORM\ManyToMany(targetEntity: Subject::class, inversedBy: 'users_related_to_subject', cascade: ['persist'])]
    #[Groups(['subject:add', 'subject:read:all'])]
    private Collection $subjects_related_to_user;

    /**
     * @var Collection<int, UserTodo>
     */
    #[ORM\OneToMany(targetEntity: UserTodo::class, mappedBy: 'user', orphanRemoval: true)]
    #[Groups(["user:read:todos"])]
    private Collection $userTodos;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['user:work'])]
    private array $workingHours = [
        'template' => [],
        'individual' => []
    ];

    /**
     * @var Collection<int, Appointment>
     */
    #[ORM\ManyToMany(targetEntity: Appointment::class, mappedBy: 'users')]
    private Collection $appointments;

    /**
     * @var Collection<int, Appointment>
     */
    #[ORM\OneToMany(targetEntity: Appointment::class, mappedBy: 'creator')]
    private Collection $createdAppointments;

    /**
     * @var Collection<int, TipUser>
     */
    #[ORM\OneToMany(targetEntity: TipUser::class, mappedBy: 'user', orphanRemoval: true)]
    #[Groups(['tip:read:user'])]
    private Collection $tipUsers;

    /**
     * @var Collection<int, Tip>
     */
    #[ORM\OneToMany(targetEntity: Tip::class, mappedBy: 'creator')]
    #[Groups(['tip:read:user'])]
    private Collection $createdTips;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:pfp'])]
    private ?string $pfpPath = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_deleted = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deletionRequestToken = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateCreated = null;

    #[ORM\Column(length: 2000, nullable: true)]
    private ?string $medicalInformation = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $gender = null;

    #[ORM\Column]
    private ?bool $firstLogin = true;

    public function __construct()
    {
        $this->wards = new ArrayCollection();
        $this->exams = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->createdTodos = new ArrayCollection();
        $this->subjects_related_to_user = new ArrayCollection();
        $this->userTodos = new ArrayCollection();
        $this->appointments = new ArrayCollection();
        $this->createdAppointments = new ArrayCollection();
        $this->tipUsers = new ArrayCollection();
        $this->createdTips = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): static
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(string $last_name): static
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getLearningLevel(): ?string
    {
        return $this->learning_level;
    }

    public function setLearningLevel(string $learning_level): static
    {
        $this->learning_level = $learning_level;

        return $this;
    }

    public function getGuardian(): ?self
    {
        return $this->guardian;
    }

    public function setGuardian(?self $guardian): static
    {
        $this->guardian = $guardian;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getWards(): Collection
    {
        return $this->wards;
    }

    public function addGuardian(self $user): static
    {
        if (!$this->wards->contains($user)) {
            $this->wards->add($user);
            $user->setGuardian($this);
        }

        return $this;
    }

    public function removeGuardian(self $user): static
    {
        if ($this->wards->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getGuardian() === $this) {
                $user->setGuardian(null);
            }
        }

        return $this;
    }

    public function getPrivateLocation(): ?Location
    {
        return $this->private_location;
    }

    public function setPrivateLocation(?Location $private_location): static
    {
        $this->private_location = $private_location;

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
            $exam->setUserTakingExam($this);
        }

        return $this;
    }

    public function removeExam(Exam $exam): static
    {
        if ($this->exams->removeElement($exam)) {
            // set the owning side to null (unless already changed)
            if ($exam->getUserTakingExam() === $this) {
                $exam->setUserTakingExam(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->addUser($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            $notification->removeUser($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Todo>
     */
    public function getCreatedTodos(): Collection
    {
        return $this->createdTodos;
    }

    public function addCreatedTodo(Todo $createdTodo): static
    {
        if (!$this->createdTodos->contains($createdTodo)) {
            $this->createdTodos->add($createdTodo);
            $createdTodo->setCreator($this);
        }

        return $this;
    }

    public function removeCreatedTodo(Todo $createdTodo): static
    {
        if ($this->createdTodos->removeElement($createdTodo)) {
            // set the owning side to null (unless already changed)
            if ($createdTodo->getCreator() === $this) {
                $createdTodo->setCreator(null);
            }
        }

        return $this;
    }

    public function getBirthday(): ?\DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(\DateTimeInterface $birthday): static
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getCompanyLocation(): ?Location
    {
        return $this->company_location;
    }

    public function setCompanyLocation(?Location $company_location): static
    {
        $this->company_location = $company_location;

        return $this;
    }

    /**
     * @return Collection<int, Subject>
     */
    public function getSubjectsRelatedToUser(): Collection
    {
        return $this->subjects_related_to_user;
    }

    public function addSubjectsRelatedToUser(Subject $subjectsRelatedToUser): static
    {
        if (!$this->subjects_related_to_user->contains($subjectsRelatedToUser)) {
            $this->subjects_related_to_user->add($subjectsRelatedToUser);
        }

        return $this;
    }

    public function removeSubjectsRelatedToUser(Subject $subjectsRelatedToUser): static
    {
        $this->subjects_related_to_user->removeElement($subjectsRelatedToUser);

        return $this;
    }

    /**
     * @return Collection<int, UserTodo>
     */
    public function getUserTodos(): Collection
    {
        return $this->userTodos;
    }

    public function addUserTodo(UserTodo $userTodo): static
    {
        if (!$this->userTodos->contains($userTodo)) {
            $this->userTodos->add($userTodo);
            $userTodo->setUser($this);
        }

        return $this;
    }

    public function removeUserTodo(UserTodo $userTodo): static
    {
        if ($this->userTodos->removeElement($userTodo)) {
            // set the owning side to null (unless already changed)
            if ($userTodo->getUser() === $this) {
                $userTodo->setUser(null);
            }
        }

        return $this;
    }

    public function getWorkingHours(): WorkingHours
    {
        return new WorkingHours(
            $this->workingHours['template'] ?? [],
            $this->workingHours['individual'] ?? []
        );
    }

    public function setWorkingHours(WorkingHours $workingHours): static
    {
        $this->workingHours = [
            'template' => $workingHours->template,
            'individual' => $workingHours->individual
        ];

        return $this;
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function getAppointments(): Collection
    {
        return $this->appointments;
    }

    public function addAppointment(Appointment $appointment): static
    {
        if (!$this->appointments->contains($appointment)) {
            $this->appointments->add($appointment);
            $appointment->addUser($this);
        }

        return $this;
    }

    public function removeAppointment(Appointment $appointment): static
    {
        if ($this->appointments->removeElement($appointment)) {
            $appointment->removeUser($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function getCreatedAppointments(): Collection
    {
        return $this->createdAppointments;
    }

    public function addCreatedAppointment(Appointment $createdAppointment): static
    {
        if (!$this->createdAppointments->contains($createdAppointment)) {
            $this->createdAppointments->add($createdAppointment);
            $createdAppointment->setCreator($this);
        }

        return $this;
    }

    public function removeCreatedAppointment(Appointment $createdAppointment): static
    {
        if ($this->createdAppointments->removeElement($createdAppointment)) {
            // set the owning side to null (unless already changed)
            if ($createdAppointment->getCreator() === $this) {
                $createdAppointment->setCreator(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TipUser>
     */
    public function getTipUsers(): Collection
    {
        return $this->tipUsers;
    }

    public function addTipUser(TipUser $tipUser): static
    {
        if (!$this->tipUsers->contains($tipUser)) {
            $this->tipUsers->add($tipUser);
            $tipUser->setUser($this);
        }

        return $this;
    }

    public function removeTipUser(TipUser $tipUser): static
    {
        if ($this->tipUsers->removeElement($tipUser)) {
            // set the owning side to null (unless already changed)
            if ($tipUser->getUser() === $this) {
                $tipUser->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Tip>
     */
    public function getCreatedTips(): Collection
    {
        return $this->createdTips;
    }

    public function addCreatedTip(Tip $createdTip): static
    {
        if (!$this->createdTips->contains($createdTip)) {
            $this->createdTips->add($createdTip);
            $createdTip->setCreator($this);
        }

        return $this;
    }

    public function removeCreatedTip(Tip $createdTip): static
    {
        if ($this->createdTips->removeElement($createdTip)) {
            // set the owning side to null (unless already changed)
            if ($createdTip->getCreator() === $this) {
                $createdTip->setCreator(null);
            }
        }

        return $this;
    }

    public function getPfpPath(): ?string
    {
        return $this->pfpPath;
    }

    public function setPfpPath(?string $pfpPath): static
    {
        $this->pfpPath = $pfpPath;

        return $this;
    }

    public function isDeleted(): ?bool
    {
        return $this->is_deleted;
    }

    public function setIsDeleted(?bool $is_deleted): static
    {
        $this->$is_deleted = $is_deleted;

        return $this;
    }

    public function getDeletionRequestToken(): ?string
    {
        return $this->deletionRequestToken;
    }

    public function setDeletionRequestToken(?string $deletionRequestToken): static
    {
        $this->deletionRequestToken = $deletionRequestToken;

        return $this;
    }

    public function getDateCreated(): ?\DateTimeInterface
    {
        return $this->dateCreated;
    }

    public function setDateCreated(\DateTimeInterface $dateCreated): static
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    public function getMedicalInformation(): ?string
    {
        return $this->medicalInformation;
    }

    public function setMedicalInformation(?string $medicalInformation): static
    {
        $this->medicalInformation = $medicalInformation;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getGender(): ?int
    {
        return $this->gender;
    }

    public function setGender(int $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function isFirstLogin(): ?bool
    {
        return $this->firstLogin;
    }

    public function setFirstLogin(bool $firstLogin): static
    {
        $this->firstLogin = $firstLogin;

        return $this;
    }
}
