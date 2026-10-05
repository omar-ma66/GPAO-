<?php

namespace App\Security\Voter;

use App\Entity\OrdreFabrication;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
class OrdreFabricationVoter extends Voter
{
    public const EDIT = 'EDIT';

    protected function supports(
        string $attribute,
        mixed $subject
    ): bool {
        return $attribute === self::EDIT
            && $subject instanceof OrdreFabrication;
    }

    protected function voteOnAttribute(string $attribute,mixed $subject, TokenInterface $token,?Vote $vote=null
    ): bool {
        /** @var OrdreFabrication $ordre */
        $ordre = $subject;

        $user = $token->getUser();

        // Personne non connectée
        if (!$user instanceof User) {
            return false;
        }

        // L'administrateur peut modifier tous les ordres
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        // Un utilisateur normal peut uniquement modifier ses propres ordres
        return $ordre->getUser() === $user;
    }
}
