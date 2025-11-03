<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Repository\ConversationRepository;
use App\State\ConversationProcessor;
use App\State\ConversationProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ConversationRepository::class)]
#[ApiResource(
    description: 'A conversation between two users.',
    operations: [
        new GetCollection(
            uriTemplate: '/chat/conversations/',
            paginationEnabled: false,
            description: 'Get all existing conversations for the current user.',
            normalizationContext: ['groups' => ['conversation:basic', 'user:basic', 'message:read']],
            name: 'getExistingChatsForUser',
            provider: ConversationProvider::class
        ),
        new Post(
            uriTemplate: '/chat/new',
            normalizationContext: ['groups' => ['conversation:basic']],
            denormalizationContext: ['groups' => ['conversation:create']],
            name: 'createNewConversation',
            processor: ConversationProcessor::class
        ),
    ]
)]
class Conversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['conversation:full', 'conversation:basic'])]
    private ?int $id = null;

    /**
     * @var Collection<int, Message>
     */
    #[Groups(['conversation:full'])]
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'conversation', orphanRemoval: true)]
    private Collection $messages;

    #[Groups(['conversation:full', 'conversation:basic'])]
    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Message $last_message = null;

    #[Groups(['conversation:full', 'conversation:basic'])]
    #[ORM\ManyToOne]
    private ?User $initiator = null;

    #[Groups(['conversation:full', 'conversation:basic', 'conversation:create'])]
    #[ORM\ManyToOne]
    private ?User $recipient = null;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setConversation($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getConversation() === $this) {
                $message->setConversation(null);
            }
        }

        return $this;
    }

    public function getLastMessage(): ?Message
    {
        return $this->last_message;
    }

    public function setLastMessage(?Message $last_message): static
    {
        $this->last_message = $last_message;

        return $this;
    }

    public function getInitiator(): ?User
    {
        return $this->initiator;
    }

    public function setInitiator(?User $initiator): static
    {
        $this->initiator = $initiator;

        return $this;
    }

    public function getRecipient(): ?User
    {
        return $this->recipient;
    }

    public function setRecipient(?User $recipient): static
    {
        $this->recipient = $recipient;

        return $this;
    }
}
