<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class SubjectVoter extends Voter
{
    public const PLANNING = 'SUBJECT_APPOINTMENT_PLANNING';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, [self::PLANNING]);
            //&& $subject instanceof \App\Entity\Subject;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user instanceof User) {
            return false;
        }

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case self::PLANNING:
                return $user->getRoles()[0] == "ROLE_OFFICE" || $user->getRoles()[0] == "ROLE_LOCATION_MANAGEMENT" || $user->getRoles()[0] == "ROLE_MANAGEMENT";
        }
        return false;
    }
}
