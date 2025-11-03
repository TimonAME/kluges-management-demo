<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\TipCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: TipCategoryRepository::class)]
#[ApiResource(
    description: 'Die Kategorie der Tipps.',
    operations: [
        new GetCollection(
            uriTemplate: '/tipCategory',
            normalizationContext: ['groups' => ['tipCategory:all']],
            name: 'getAllTipCategories'
        )
    ],
    formats: [
        'json'
    ],
    outputFormats: ['json'],
    normalizationContext: [
        'groups' => ['tipCategory:all'],
    ]
)]
class TipCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['tipCategory:all', 'tip:create', 'tip:read:all'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['tipCategory:all', 'tip:read:all'])]
    private ?string $name = null;

    /**
     * @var Collection<int, Tip>
     */
    #[ORM\OneToMany(targetEntity: Tip::class, mappedBy: 'tipCategory')]
    private Collection $tips;

    public function __construct()
    {
        $this->tips = new ArrayCollection();
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
     * @return Collection<int, Tip>
     */
    public function getTips(): Collection
    {
        return $this->tips;
    }

    public function addTip(Tip $tip): static
    {
        if (!$this->tips->contains($tip)) {
            $this->tips->add($tip);
            $tip->setTipCategory($this);
        }

        return $this;
    }

    public function removeTip(Tip $tip): static
    {
        if ($this->tips->removeElement($tip)) {
            // set the owning side to null (unless already changed)
            if ($tip->getTipCategory() === $this) {
                $tip->setTipCategory(null);
            }
        }

        return $this;
    }
}
