<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Patch;
use App\Repository\NotificationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ApiResource(
    description: 'Eine TODO die erledigt werden muss.',
    operations: [
        new Patch(
            name: "update_notofication_with_check",
            uriTemplate: "/notification/{id}/read",
            denormalizationContext: ['groups' => ['notification_change_view']],
            normalizationContext: ['groups' => ['notification_basic_view']]
        )
    ],
    formats: [
        'json'
    ],
    outputFormats: ['json'],
    normalizationContext: [
        'groups' => ['notification_basic_view'],
    ]
)]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['notification_basic_view'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['notification_basic_view'])]
    private ?string $message = null;

    #[ORM\Column]
    #[Groups(['notification_basic_view', 'notification_change_view'])]
    private ?bool $isRead = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['notification_basic_view'])]
    private ?\DateTimeInterface $date_created = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'notifications')]
    #[Groups(['notification_user_view'])]
    private Collection $users;

    /**
     * @var Collection<int, NotificationTag>
     */
    #[ORM\ManyToMany(targetEntity: NotificationTag::class, mappedBy: 'notifications')]
    #[Groups(['notification_basic_view'])]
    private Collection $notificationTags;

    public function __construct()
    {
        $this->notificationTags = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getIsRead(): ?bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;

        return $this;
    }

    public function getDateCreated(): ?\DateTimeInterface
    {
        return $this->date_created;
    }

    public function setDateCreated(\DateTimeInterface $date_created): static
    {
        $this->date_created = $date_created;

        return $this;
    }


    /**
     * @return Collection<int, NotificationTag>
     */
    public function getNotificationTags(): Collection
    {
        return $this->notificationTags;
    }

    public function addNotificationTag(NotificationTag $notificationTag): static
    {
        if (!$this->notificationTags->contains($notificationTag)) {
            $this->notificationTags->add($notificationTag);
            $notificationTag->addNotification($this);
        }

        return $this;
    }

    public function removeNotificationTag(NotificationTag $notificationTag): static
    {
        if ($this->notificationTags->removeElement($notificationTag)) {
            $notificationTag->removeNotification($this);
        }

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
}
