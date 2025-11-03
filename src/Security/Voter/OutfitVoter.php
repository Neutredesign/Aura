<?php

namespace App\Security\Voter;

use App\Entity\Outfit;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class OutfitVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Outfit
            && in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var Outfit $outfit */
        $outfit = $subject;

        $isOwner = $outfit->getUser() && $outfit->getUser()->getId() === $user->getId();
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);

        return match ($attribute) {
            self::VIEW, self::EDIT, self::DELETE => $isOwner || $isAdmin,
        };
    }
}
