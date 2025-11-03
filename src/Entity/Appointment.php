<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\AppointmentRepository;
use App\State\AppointmentProcessor;
use App\State\AppointmentProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: AppointmentRepository::class)]
#[ApiResource(
    description: "Ein bevorstehender Termin.",
    operations: [
        new Patch(
            uriTemplate: "/appointment/{id}/note",
            normalizationContext: ["groups" => ["appointment:notes"]],
            denormalizationContext: ["groups" => ["appointment:notes"]],
            security: "is_granted('APPOINTMENT_NOTE', object)",
            name: 'addNotes'
        ),

        new Patch(
            uriTemplate: "/appointment/{id}/attendance",
            normalizationContext: ["groups" => ["appointment:attendance"]],
            denormalizationContext: ["groups" => ["appointment:attendance"]],
            security: "is_granted('APPOINTMENT_NOTE', object)",
            name: 'updateAttendance'
        ),

        new Patch(
            uriTemplate: "/appointment/{id}/homework",
            normalizationContext: ["groups" => ["appointment:homework"]],
            denormalizationContext: ["groups" => ["appointment:homework"]],
            security: "is_granted('APPOINTMENT_NOTE', object)",
            name: 'updateHomework'
        ),

        new Post(
            uriTemplate: "/appointment",
            denormalizationContext: ['groups' => ['appointment:create']],
            name: 'createAppointment',
            processor: AppointmentProcessor::class
        ),

        new Put(
            uriTemplate: "/appointment/{id}",
            denormalizationContext: ['groups' => ['appointment:create']],
            security: "is_granted('APPOINTMENT_EDIT', object)",
            name: 'updateAppointment',
            processor: AppointmentProcessor::class
        ),

        new Put(
            uriTemplate: "/appointment/{id}/delete",
            denormalizationContext: ['groups' => ['appointment:delete:user']],
            security: "is_granted('APPOINTMENT_EDIT', object)",
            name: 'removeAssignedUser',
            processor: AppointmentProcessor::class
        ),

        new Get(
            uriTemplate: "/appointment/{id}",
            normalizationContext: ['groups' => ['appointment:view:detail', 'appointment:view:users']],
            security: "is_granted('APPOINTMENT_VIEW', object)",
            name: 'readAppointment',
        ),

        new GetCollection(
            uriTemplate: "/appointment",
            normalizationContext: ['groups' => ['appointment:view:detail', 'appointment:view:users']],
            name: 'readAll',
            provider: AppointmentProvider::class
        ),

        new GetCollection(
            uriTemplate: "/appointment/get/today",
            normalizationContext: ['groups' => ['appointment:view:detail', 'appointment:view:users']],
            name: 'readAllToday',
            provider: AppointmentProvider::class
        ),

        new GetCollection(
            uriTemplate: "/appointment/get/twoMonths",
            normalizationContext: ['groups' => ['appointment:view:detail', 'appointment:view:users']],
            name: 'readAllTwoMonths',
            provider: AppointmentProvider::class
        ),

        new GetCollection(
            uriTemplate: "/appointment/get/available-rooms",
            paginationEnabled: false,
            normalizationContext: ["groups" => ["room:read"]],
            security: "is_granted('APPOINTMENT_PLANNING', object)",
            name: 'getAvailableRooms',
            provider: AppointmentProvider::class
        ),

        new GetCollection(
            uriTemplate: "/appointment/tutoring/{id}",
            normalizationContext: ["groups" => ["appointment:view:detail"]],
            name: 'getTutoringAppointment',
            provider: AppointmentProvider::class
        ),

        new Delete(
            uriTemplate: "/appointment/{id}",
            security: "is_granted('APPOINTMENT_EDIT', object)",
            name: "deleteAppointment",
            processor: AppointmentProcessor::class
        ),

        ## Filteroptionen ##
        new GetCollection(
            uriTemplate: "/appointment/search/title",
            normalizationContext: ['groups' => ['appointment:view:detail']],
            name: 'searchByTitle',
            provider: AppointmentProvider::class
        ),

        new GetCollection(
            uriTemplate: "/appointment/search/timespan",
            normalizationContext: ['groups' => ['appointment:view:detail']],
            name: 'searchByTimespan',
            provider: AppointmentProvider::class
        ),

        new GetCollection(
            uriTemplate: "/appointment/search/category",
            normalizationContext: ['groups' => ['appointment:view:detail']],
            name: 'searchByCategory',
            provider: AppointmentProvider::class
        ),

        new GetCollection(
            uriTemplate: "/appointment/search/tutoring/timespan/{id}",
            normalizationContext: ['groups' => ['appointment:tutoring:minimal']],
            security: "is_granted('APPOINTMENT_PLANNING', object)",
            name: 'searchTutoringByTimespan',
            provider: AppointmentProvider::class
        ),

        new GetCollection(
            uriTemplate: "/appointment/search/tutoring/user/{id}",
            normalizationContext: ['groups' => ['appointment:tutoring:minimal']],
            name: 'searchTutoringByUser',
            provider: AppointmentProvider::class
        )
    ],
    formats: ['json'],
    outputFormats: ['json'],
    normalizationContext: [
        'groups' => ['appointment:create'],
    ]
)]
class Appointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['appointment:view:detail', 'appointment:tutoring:minimal'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['appointment:create', 'appointment:view:detail', 'appointment:tutoring:minimal'])]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['appointment:create', 'appointment:view:detail', 'appointment:tutoring:minimal'])]
    private ?\DateTimeInterface $endTime = null;

    #[ORM\ManyToOne(inversedBy: 'appointments')]
    #[Groups(['appointment:create', 'appointment:view:detail'])]
    private ?Location $location = null;

    #[ORM\ManyToOne(inversedBy: 'appointments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['appointment:create', 'appointment:view:detail'])]
    private ?AppointmentCategory $appointmentCategory = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'appointments')]
    #[Groups(['appointment:create', 'appointment:view:users', 'appointment:delete:user', 'appointment:view:detail'])]
    private Collection $users;

    #[ORM\Column(length: 255)]
    #[Groups(['appointment:create', 'appointment:view:detail'])]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    #[Groups(['appointment:create', 'appointment:view:detail'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['appointment:create', 'appointment:view:detail'])]
    private ?bool $allDay = null;

    #[ORM\Column(length: 7)]
    #[Groups(['appointment:create', 'appointment:view:detail'])]
    private ?string $color = null;

    #[ORM\ManyToOne(inversedBy: 'createdAppointments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['appointment:view:detail'])]
    private ?User $creator = null;

    #[ORM\ManyToOne(inversedBy: 'appointments')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['appointment:create', 'appointment:view:detail'])]
    private ?Room $room = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['appointment:create', 'appointment:view:detail', 'appointment:notes'])]
    private ?array $notes = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['appointment:create', 'appointment:view:detail'])]
    private ?User $teacher = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['appointment:create', 'appointment:view:detail', 'appointment:attendance'])]
    private ?array $attendance = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['appointment:create', 'appointment:view:detail', 'appointment:homework'])]
    private ?array $homework = null;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeInterface $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getAppointmentCategory(): ?AppointmentCategory
    {
        return $this->appointmentCategory;
    }

    public function setAppointmentCategory(?AppointmentCategory $appointmentCategory): static
    {
        $this->appointmentCategory = $appointmentCategory;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        $this->users->removeElement($user);

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isAllDay(): ?bool
    {
        return $this->allDay;
    }

    public function setAllDay(bool $allDay): static
    {
        $this->allDay = $allDay;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): static
    {
        $this->creator = $creator;

        return $this;
    }

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room): static
    {
        $this->room = $room;

        return $this;
    }

    public function getNotes(): ?array
    {
        return $this->notes;
    }

    public function setNotes(?array $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getTeacher(): ?User
    {
        return $this->teacher;
    }

    public function setTeacher(?User $teacher): static
    {
        $this->teacher = $teacher;

        return $this;
    }

    public function getAttendance(): ?array
    {
        return $this->attendance;
    }

    public function setAttendance(?array $attendance): static
    {
        $this->attendance = $attendance;

        return $this;
    }

    public function getHomework(): ?array
    {
        return $this->homework;
    }

    public function setHomework(?array $homework): static
    {
        $this->homework = $homework;

        return $this;
    }
}
