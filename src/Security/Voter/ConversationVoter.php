<?php

namespace App\Security\Voter;

use App\Entity\Conversation;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class ConversationVoter extends Voter
{
    public const EDIT = 'CONVERSATION_EDIT';
    public const VIEW = 'CONVERSATION_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW])
            && $subject instanceof Conversation;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Conversation $conversation */
        $conversation = $subject;

        // Check if user is initiator or recipient of the conversation
        $isParticipant = $conversation->getInitiator() === $user || $conversation->getRecipient() === $user;

        switch ($attribute) {
            case self::EDIT:
            case self::VIEW:
                return $isParticipant;

        }

        return false;
    }
}