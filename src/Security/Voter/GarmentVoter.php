<?php

namespace App\Security\Voter;

use App\Entity\Garment;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Ce voter contrôle les droits d’accès sur les entités Garment (vêtements)
 * Il permet de vérifier si l’utilisateur peut VOIR, MODIFIER ou SUPPRIMER
 * un vêtement (appartenance à l’utilisateur ou rôle admin).
 */
class GarmentVoter extends Voter
{
    public const VIEW = 'GARMENT_VIEW';
    public const EDIT = 'GARMENT_EDIT';
    public const DELETE = 'GARMENT_DELETE';

    /**
     * Vérifie si ce voter s’applique à l’attribut et au sujet donnés
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof Garment;
    }

    /**
     * Logique de décision : autorise ou refuse l’accès
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            // utilisateur non connecté → accès refusé
            return false;
        }

        /** @var Garment $garment */
        $garment = $subject;
        $isOwner = $garment->getUser()?->getId() === $user->getId();

        return match ($attribute) {
            self::VIEW, self::EDIT, self::DELETE => $isOwner || in_array('ROLE_ADMIN', $user->getRoles(), true),
            default => false,
        };
    }
}
