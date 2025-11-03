<?php

namespace App\Security\Voter;

use App\Entity\Message;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class MessageVoter extends Voter
{
    public const EDIT = 'MESSAGE_EDIT';
    public const VIEW = 'MESSAGE_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW])
            && $subject instanceof Message;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Message $message */
        $message = $subject;
        $conversation = $message->getConversation();

        // Check if user is part of the conversation
        $isParticipant = $conversation->getInitiator() === $user || $conversation->getRecipient() === $user;

        switch ($attribute) {
            case self::VIEW:
                return $isParticipant;

            case self::EDIT:
                // Only message sender can edit the message
                return $message->getSender() === $user;
        }

        return false;
    }
}