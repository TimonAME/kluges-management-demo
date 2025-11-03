<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\QueryParameter;
use App\Repository\TodoRepository;
use App\State\TodoProcessor;
use App\State\TodoProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Serializer\Attribute\MaxDepth;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TodoRepository::class)]
#[ApiResource(
    description: 'Ein TODO das erledigt werden muss.',
    operations: [
        new Patch(
            uriTemplate: "/todo/{id}/check",
            denormalizationContext: ['groups' => ['check']],
            security: "is_granted('TODO_CHECK', object)",
            name: "checkTodo",
            processor: TodoProcessor::class
        ),

        new Post(
            uriTemplate: "/todo",
            normalizationContext: ['groups' => ['todo:read:all', 'todo:read:detail']],
            denormalizationContext: ['groups' => ['todo:create']],
            name: "createTodo",
            processor: TodoProcessor::class,
        ),

        new Put(
            uriTemplate: "/todo/{id}",
            normalizationContext: ['groups' => ['todo:read:all', 'todo:read:detail']],
            denormalizationContext: ['groups' => ['todo:create']],
            security: "is_granted('TODO_EDIT', object)",
            name: "updateTodo",
            processor: TodoProcessor::class,
        ),

        new Get(
            uriTemplate: "/todo/{id}",
            normalizationContext: ['groups' => ['todo:read:all', 'todo:read:detail']],
            security: "is_granted('TODO_VIEW', object)",
            name: "read"
        ),

        new GetCollection(
            uriTemplate: "/todo",
            normalizationContext: ['groups' => ['todo:read:all']],
            name: "readAllForUser",
            provider: TodoProvider::class
        ),

        new GetCollection(
            uriTemplate: "/todo/search/todos",
            normalizationContext: ['groups' => ['todo:read:all']],
            name: "searchTodos",
            provider: TodoProvider::class
        ),

        new Delete(
            uriTemplate: "/todo/{id}",
            security: "is_granted('TODO_EDIT', object)",
            name: "deleteTodo",
        ),

        new Put(
            uriTemplate: "/todo/{id}/delete",
            denormalizationContext: ['groups' => ['todo:delete:UserTodo']],
            security: "is_granted('TODO_EDIT', object)",
            name: "deleteUserTodo",
            processor: TodoProcessor::class
        )
    ],
    formats: [
        'json'
    ],
    outputFormats: ['json'],
    normalizationContext: [
        'groups' => ['todo:read:all'],
        ]
)]
class Todo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['todo:read:basic', 'todo:read:all'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['todo:read:basic', 'todo:create', 'todo:read:all'])]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    #[Groups(['todo:read:basic', 'todo:create', 'todo:read:all'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dueDate = null;

    #[ORM\ManyToOne(inversedBy: 'createdTodos')]
    #[Groups(['participant_view', 'todo:read:all'])]
    private ?User $creator = null;

    /**
     * @var Collection<int, UserTodo>
     */
    #[ORM\OneToMany(targetEntity: UserTodo::class, mappedBy: 'todo', orphanRemoval: true)]
    #[Groups(['todo:create', 'todo:read:detail', 'todo:delete:UserTodo', "user:read:todos"])]
    private Collection $userTodos;


    public function __construct()
    {
        $this->userTodos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): static
    {
        $this->creator = $creator;

        return $this;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeInterface $dueDate): static
    {
        $this->dueDate = $dueDate;

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
            $userTodo->setTodo($this);
        }

        return $this;
    }

    public function removeUserTodo(UserTodo $userTodo): static
    {
        if ($this->userTodos->removeElement($userTodo)) {
            // set the owning side to null (unless already changed)
            if ($userTodo->getTodo() === $this) {
                $userTodo->setTodo(null);
            }
        }

        return $this;
    }
}
