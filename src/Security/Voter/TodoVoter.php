<?php

namespace App\Security\Voter;

use App\Entity\Todo;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class TodoVoter extends Voter
{
    public const EDIT = 'TODO_EDIT';
    public const VIEW = 'TODO_VIEW';
    public const CHECK = 'TODO_CHECK';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, [self::EDIT, self::VIEW, self::CHECK])
            && $subject instanceof Todo;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {

        assert($subject instanceof Todo);

        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case self::VIEW:
                if ($subject->getCreator() === $user) {
                    return true;
                }

                foreach ($subject->getUserTodos() as $userTodo) {
                    if ($userTodo->getUser() === $user) {
                        return true;
                    }
                }
                break;

            case self::EDIT:
                if ($subject->getCreator() === $user) {
                    return true;
                }
                break;

            case self::CHECK:
                foreach ($subject->getUserTodos() as $userTodo) {
                    if ($userTodo->getUser() === $user) {
                        return true;
                    }
                }
                break;
        }

        return false;
    }
}
