<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Repository\AppointmentCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: AppointmentCategoryRepository::class)]
#[ApiResource(
    description: 'Kategorie von Appointment',
    operations: [
        new Post(
            uriTemplate: '/appointmentCategory',
            denormalizationContext: ['groups' => ['category:create']],
            name: 'createCategory'
        ),
        new Delete(
            uriTemplate: '/appointmentCategory/{id}',
            security: "is_granted('POST_EDIT', object)",
            name: 'deleteCategory'
        ),
        new GetCollection(
            uriTemplate: '/appointmentCategory',
            normalizationContext: ['groups' => ['category:read']],
            name: 'getCategories'
        )
    ],
    formats: [
        'json'
    ],
)]
class AppointmentCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['appointment_categories', 'appointment:create', 'category:read', 'appointment:view:detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['appointment_categories', 'category:create', 'category:read', 'appointment:view:detail'])]
    private ?string $name = null;

    /**
     * @var Collection<int, Appointment>
     */
    #[ORM\OneToMany(targetEntity: Appointment::class, mappedBy: 'appointmentCategory')]
    private Collection $appointments;


    public function __construct()
    {
        $this->appointments = new ArrayCollection();
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
            $appointment->setAppointmentCategory($this);
        }

        return $this;
    }

    public function removeAppointment(Appointment $appointment): static
    {
        if ($this->appointments->removeElement($appointment)) {
            // set the owning side to null (unless already changed)
            if ($appointment->getAppointmentCategory() === $this) {
                $appointment->setAppointmentCategory(null);
            }
        }

        return $this;
    }
}
