<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class TipVoter extends Voter
{
    public const EDIT = 'TIP_EDIT';
    public const VIEW = 'TIP_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW])
            && $subject instanceof \App\Entity\Tip;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case self::EDIT:
                if ($subject->getCreator() === $user)
                    return true;
                break;

            case self::VIEW:
                if ($user->getRoles()[0] != "ROLE_GUARDIAN" && $user->getRoles()[0] != "ROLE_STUDENT")
                    return true;
                break;
        }

        return false;
    }
}
