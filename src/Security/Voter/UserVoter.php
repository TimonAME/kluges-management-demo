<?php

namespace App\Security\Voter;

use phpDocumentor\Reflection\Types\Self_;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserVoter extends Voter
{
    public const EDIT = 'USER_EDIT';
    public const VIEW = 'USER_VIEW';
    public const WORK = 'USER_WORK';
    public const WVIEW = 'USER_WORK_VIEW';
    public const DELETE = 'USER_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, [self::EDIT, self::VIEW, self::WORK, self::WVIEW])
            && $subject instanceof \App\Entity\User;
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
                // logic to determine if the user can EDIT
                // return true or false
            case self::VIEW:
                if ($user === $subject || in_array("ROLE_MANAGEMENT", $user->getRoles()))
                    return true;
                break;
            case self::WORK:
                return $user === $subject;
                break;
            case self::WVIEW:
                return $user === $subject || $user->getRoles()[0] == "ROLE_OFFICE" || $user->getRoles()[0] == "ROLE_LOCATION_MANAGEMENT" || $user->getRoles()[0] == "ROLE_MANAGEMENT";
            case self::DELETE:
                return $user->getRoles()[0] == "ROLE_OFFICE" || $user->getRoles()[0] == "ROLE_LOCATION_MANAGEMENT" || $user->getRoles()[0] == "ROLE_MANAGEMENT";
        }

        return false;
    }
}
