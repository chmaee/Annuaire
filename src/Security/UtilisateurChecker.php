<?php
// src/Security/UserChecker.php

namespace App\Security;

use App\Entity\Utilisateur;
use App\Service\UtilisateurService;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UtilisateurChecker implements UserCheckerInterface
{
    private UtilisateurService $utilisateurService;

    public function __construct(UtilisateurService $utilisateurService)
    {
        $this->utilisateurService = $utilisateurService;
    }

    public function checkPreAuth(UserInterface $user): void
    {
        // Aucune action pré-authentification spécifique
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof Utilisateur) {
            return;
        }

        $this->utilisateurService->updateLastConnection($user);
    }
}
