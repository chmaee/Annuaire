<?php

namespace App\Service;

use App\Entity\Utilisateur;
use App\Exception\CodeAlreadyUsedException;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;

class UtilisateurService
{
    private UtilisateurRepository $utilisateurRepository;
    private EntityManagerInterface $entityManager;
    private UtilisateurManager $utilisateurManager;

    public function __construct(
        UtilisateurRepository $utilisateurRepository,
        EntityManagerInterface $entityManager,
        UtilisateurManagerInterface $utilisateurManager
    ) {
        $this->utilisateurRepository = $utilisateurRepository;
        $this->entityManager = $entityManager;
        $this->utilisateurManager = $utilisateurManager;
    }


    public function createUtilisateur(string $login, string $adresseEmail, string $plainPassword, ?string $code, bool $visible): Utilisateur
    {
        $utilisateur = new Utilisateur();
        $utilisateur->setLogin($login);
        $utilisateur->setAdresseEmail($adresseEmail);
        $utilisateur->setVisible($visible);

        if ($code && $this->utilisateurRepository->isCodeUnique($code)) {
            $utilisateur->setCode($code);
        } elseif ($code) {
            // Le code est fourni mais déjà pris
            throw new CodeAlreadyUsedException($code);
        } else {
            // Générer un code unique si aucun code n'a été fourni
            $code = $this->generateUniqueCode(null);
            $utilisateur->setCode($code);
        }

        $this->utilisateurManager->processNewUtilisateur($utilisateur, $plainPassword);

        $this->entityManager->persist($utilisateur);
        $this->entityManager->flush();

        return $utilisateur;
    }


    public function updateUtilisateur(Utilisateur $utilisateur): Utilisateur
    {
        $code = $utilisateur->getCode();

        $existingUtilisateur = $this->utilisateurRepository->findOneBy(['code' => $code]);

        if ($existingUtilisateur && $existingUtilisateur->getId() !== $utilisateur->getId()) {
            throw new CodeAlreadyUsedException($code);
        }

        $utilisateur->updateLastEdited();
        $this->entityManager->persist($utilisateur);
        $this->entityManager->flush();

        return $utilisateur;
    }

    public function deleteUtilisateur(Utilisateur $utilisateur): void
    {
        $this->entityManager->remove($utilisateur);
        $this->entityManager->flush();
    }

    public function isCodeUnique(string $code): bool
    {
        return $this->utilisateurRepository->isCodeUnique($code);
    }

    public function isCodeUniqueForExistingUser(string $code, string $login): bool
    {
        return $this->utilisateurRepository->isCodeUniqueForExistingUser($code, $login);
    }


    public function generateUniqueCode(?string $code): string
    {
        if ($code && $this->utilisateurRepository->isCodeUnique($code)) {
            return $code;
        }

        do {
            $code = bin2hex(random_bytes(4));
        } while (!$this->utilisateurRepository->isCodeUnique($code));

        return $code;
    }

    public function getVisibleUsers(): array
    {
        return $this->utilisateurRepository->findVisibleUsers();
    }

    public function updateLastConnection(Utilisateur $utilisateur): void
    {
        $utilisateur->setDateDerniereConnexion((new \DateTime())->modify('+2 hours'));
        $this->entityManager->persist($utilisateur);
        $this->entityManager->flush();
    }
}
