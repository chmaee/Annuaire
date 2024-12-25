<?php

namespace App\Service;

use App\Entity\Utilisateur;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class UtilisateurManager implements UtilisateurManagerInterface
{

    private string $dossierPhotoProfil;
    private UserPasswordHasherInterface $passwordHasher;
    private SluggerInterface $slugger;

    public function __construct(
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ) {
        $this->passwordHasher = $passwordHasher;
        $this->slugger = $slugger;
    }

    /**
     * Chiffre le mot de passe puis l'affecte au champ correspondant dans la classe de l'utilisateur
     */
    private function chiffrerMotDePasse(Utilisateur $utilisateur, ?string $plainPassword) : void {
        if ($plainPassword) {
            $hashedPassword = $this->passwordHasher->hashPassword($utilisateur, $plainPassword);
            $utilisateur->setPassword($hashedPassword);
        }
    }

    /**
     * Méthode publique pour permettre le hachage du mot de passe à l'extérieur de la classe
     */
    public function appliquerChiffrementMotDePasse(Utilisateur $utilisateur, ?string $plainPassword) : void {
        $this->chiffrerMotDePasse($utilisateur, $plainPassword);
    }

    /**
     * Réalise toutes les opérations nécessaires avant l'enregistrement en base d'un nouvel utilisateur, après soumission du formulaire (hachage du mot de passe, sauvegarde de la photo de profil...)
     */
    public function processNewUtilisateur(Utilisateur $utilisateur, ?string $plainPassword) : void {
        $this->chiffrerMotDePasse($utilisateur, $plainPassword);
    }

}
