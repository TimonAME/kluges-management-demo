<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\TipRepository;
use App\State\TipProcessor;
use App\State\TipProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: TipRepository::class)]
#[ApiResource(
    description: 'Ein Tipp.',
    operations: [
        new Post(
            uriTemplate: "/tip",
            normalizationContext: ['groups' => ['tip:read:all']],
            denormalizationContext: ['groups' => ['tip:create']],
            name: 'createTip',
            processor: TipProcessor::class
        ),

        new Patch(
            uriTemplate: "/tip/{id}",
            normalizationContext: ['groups' => ['tip:read:all']],
            denormalizationContext: ['groups' => ['tip:update']],
            security: "is_granted('TIP_EDIT', object)",
            name: 'updateTip',
        ),

        new Patch(
            uriTemplate: "/tip/{id}/read",
            normalizationContext: ['groups' => ['tip:read:all']],
            denormalizationContext: ['groups' => ['tip:read']],
            security: "is_granted('TIP_VIEW', object)",
            name: 'checkTip',
            processor: TipProcessor::class
        ),

        new GetCollection(
            uriTemplate: "/tip",
            normalizationContext: ['groups' => ['tip:read:all', 'tip:readAt', 'tip:readAt:all']],
            name: 'getAllTips'
        ),

        new GetCollection(
            uriTemplate: "/tip/readMine",
            normalizationContext: ['groups' => ['tip:read:all', 'tip:readAt']],
            name: 'getAllTipsForMe',
            provider: TipProvider::class
        ),

        new Delete(
            uriTemplate: "/tip/{id}",
            security: "is_granted('TIP_EDIT', object)",
            name: 'deleteTip'
        )
    ],
    formats: [
        'json'
    ],
    outputFormats: ['json'],
    normalizationContext: [
        'groups' => ['tip:read:all'],
    ]
)]
class Tip
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['tip:read:all', 'tip:read:user'])]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    #[Groups(['tip:create', 'tip:update', 'tip:read:user'])]
    private ?string $message = null;

    #[ORM\ManyToOne(inversedBy: 'tips')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['tip:create', 'tip:read:all'])]
    private ?TipCategory $tipCategory = null;

    #[ORM\Column(length: 255)]
    #[Groups(['tip:create', 'tip:read:all', 'tip:update', 'tip:read:user'])]
    private ?string $title = null;

    /**
     * @var Collection<int, TipUser>
     */
    #[ORM\OneToMany(targetEntity: TipUser::class, mappedBy: 'tip', orphanRemoval: true)]
    #[Groups(['tip:readAt'])]
    private Collection $tipUsers;

    #[ORM\ManyToOne(inversedBy: 'createdTips')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['tip:read:all', 'tip:read'])]
    private ?User $creator = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['tip:read:all', 'tip:read:user'])]
    private ?\DateTimeInterface $creationDate = null;

    public function __construct()
    {
        $this->tipUsers = new ArrayCollection();
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

    public function getTipCategory(): ?TipCategory
    {
        return $this->tipCategory;
    }

    public function setTipCategory(?TipCategory $tipCategory): static
    {
        $this->tipCategory = $tipCategory;

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
            $tipUser->setTip($this);
        }

        return $this;
    }

    public function removeTipUser(TipUser $tipUser): static
    {
        if ($this->tipUsers->removeElement($tipUser)) {
            // set the owning side to null (unless already changed)
            if ($tipUser->getTip() === $this) {
                $tipUser->setTip(null);
            }
        }

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

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTimeInterface $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }
}
