<?php
namespace App\Security\Voter;

use App\Entity\Utilisateur;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class UtilisateurVoter extends Voter
{
    public const EDIT = 'USER_EDIT';
    public const DELETE = 'USER_DELETE';

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE]) && $subject instanceof Utilisateur;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var Utilisateur $utilisateur */
        $utilisateur = $subject;

        if ($this->security->isGranted('ROLE_ADMIN')) {
            switch ($attribute) {
                case self::EDIT:
                case self::DELETE:
                    return $user === $utilisateur || !in_array('ROLE_ADMIN', $utilisateur->getRoles());
            }
        }
        switch ($attribute) {
            case self::EDIT:
            case self::DELETE:
                return $user === $utilisateur;
        }

        return false;
    }
}
