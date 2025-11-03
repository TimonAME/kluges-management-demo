<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Repository\RoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: RoomRepository::class)]
#[ApiResource(
    description: 'Raum',
    operations: [
        new Post(
            uriTemplate: '/room',
            denormalizationContext: ['groups' => ['room:create']],
            name: 'createRoom'
        ),
        new Delete(
            uriTemplate: '/room/{id}',
            security: "is_granted('ROOM_EDIT', object)",
            name: 'deleteRoom'
        ),
        new GetCollection(
            uriTemplate: '/room',
            normalizationContext: ['groups' => ['room:read']],
            name: 'getRoom'
        )
    ],
    formats: [
        'json'
    ],
)]
class Room
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['appointment:create', 'appointment:view:detail', 'room:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['appointment:view:detail', 'room:create', 'room:read'])]
    private ?string $roomNumber = null;

    #[ORM\ManyToOne(inversedBy: 'rooms')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['appointment:view:detail', 'room:read'])]
    private ?Location $inLocation = null;

    /**
     * @var Collection<int, Appointment>
     */
    #[ORM\OneToMany(targetEntity: Appointment::class, mappedBy: 'room')]
    private Collection $appointments;

    public function __construct()
    {
        $this->appointments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoomNumber(): ?string
    {
        return $this->roomNumber;
    }

    public function setRoomNumber(string $roomNumber): static
    {
        $this->roomNumber = $roomNumber;

        return $this;
    }

    public function getInLocation(): ?Location
    {
        return $this->inLocation;
    }

    public function setInLocation(?Location $inLocation): static
    {
        $this->inLocation = $inLocation;

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
            $appointment->setRoom($this);
        }

        return $this;
    }

    public function removeAppointment(Appointment $appointment): static
    {
        if ($this->appointments->removeElement($appointment)) {
            // set the owning side to null (unless already changed)
            if ($appointment->getRoom() === $this) {
                $appointment->setRoom(null);
            }
        }

        return $this;
    }
}
