<?php

namespace App\Entity;

use App\Repository\LocationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
class Location
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['appointment:create', 'appointment:view:detail', 'room:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['appointment:view:detail', 'user:read:advanced'])]
    private ?string $street = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['appointment:view:detail', 'user:read:advanced'])]
    private ?string $house_number = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['appointment:view:detail', 'user:read:advanced'])]
    private ?string $apartment_number = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['appointment:view:detail', 'user:read:advanced'])]
    private ?string $postal_code = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['appointment:view:detail', 'user:read:advanced', 'room:read'])]
    private ?string $city = null;

    /**
     * @var string|null $type Type of the location (On site, Online, etc.)
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'private_location')]
    private Collection $users;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'company_location')]
    private Collection $employees_at_location;

    /**
     * @var Collection<int, Room>
     */
    #[ORM\OneToMany(targetEntity: Room::class, mappedBy: 'inLocation', orphanRemoval: true)]
    private Collection $rooms;

    /**
     * @var Collection<int, Appointment>
     */
    #[ORM\OneToMany(targetEntity: Appointment::class, mappedBy: 'location')]
    private Collection $appointments;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->employees_at_location = new ArrayCollection();
        $this->rooms = new ArrayCollection();
        $this->appointments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): static
    {
        $this->street = $street;

        return $this;
    }

    public function getHouseNumber(): ?string
    {
        return $this->house_number;
    }

    public function setHouseNumber(?string $house_number): static
    {
        $this->house_number = $house_number;

        return $this;
    }

    public function getApartmentNumber(): ?string
    {
        return $this->apartment_number;
    }

    public function setApartmentNumber(?string $apartment_number): static
    {
        $this->apartment_number = $apartment_number;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postal_code;
    }

    public function setPostalCode(?string $postal_code): static
    {
        $this->postal_code = $postal_code;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

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
            $user->setPrivateLocation($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getPrivateLocation() === $this) {
                $user->setPrivateLocation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getEmployeesAtLocation(): Collection
    {
        return $this->employees_at_location;
    }

    public function addEmployeesAtLocation(User $employeesAtLocation): static
    {
        if (!$this->employees_at_location->contains($employeesAtLocation)) {
            $this->employees_at_location->add($employeesAtLocation);
            $employeesAtLocation->setCompanyLocation($this);
        }

        return $this;
    }

    public function removeEmployeesAtLocation(User $employeesAtLocation): static
    {
        if ($this->employees_at_location->removeElement($employeesAtLocation)) {
            // set the owning side to null (unless already changed)
            if ($employeesAtLocation->getCompanyLocation() === $this) {
                $employeesAtLocation->setCompanyLocation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Room>
     */
    public function getRooms(): Collection
    {
        return $this->rooms;
    }

    public function addRoom(Room $room): static
    {
        if (!$this->rooms->contains($room)) {
            $this->rooms->add($room);
            $room->setInLocation($this);
        }

        return $this;
    }

    public function removeRoom(Room $room): static
    {
        if ($this->rooms->removeElement($room)) {
            // set the owning side to null (unless already changed)
            if ($room->getInLocation() === $this) {
                $room->setInLocation(null);
            }
        }

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
            $appointment->setLocation($this);
        }

        return $this;
    }

    public function removeAppointment(Appointment $appointment): static
    {
        if ($this->appointments->removeElement($appointment)) {
            // set the owning side to null (unless already changed)
            if ($appointment->getLocation() === $this) {
                $appointment->setLocation(null);
            }
        }

        return $this;
    }
}
