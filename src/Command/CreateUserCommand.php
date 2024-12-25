<?php

// src/Command/CreateUserCommand.php
namespace App\Command;

use App\Entity\Utilisateur;
use App\Service\UtilisateurManager;
use App\Service\UtilisateurService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(
    name: 'app:create-user',
    description: 'Crée un nouvel utilisateur avec un rôle spécifié.'
)]
class CreateUserCommand extends Command
{
    private UtilisateurService $utilisateurService;
    private UtilisateurManager $utilisateurManager;
    private EntityManagerInterface $entityManager;

    public function __construct(UtilisateurService $utilisateurService, UtilisateurManager $utilisateurManager, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->utilisateurService = $utilisateurService;
        $this->utilisateurManager = $utilisateurManager;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('login', InputArgument::REQUIRED, 'Le login de la nouvelle entrée.')
            ->addArgument('password', InputArgument::REQUIRED, 'Le mot de passe pour le nouvel utilisateur.')
            ->addArgument('email', InputArgument::REQUIRED, 'L\'adresse email de l\'utilisateur.')
            ->addOption('role', null, InputOption::VALUE_REQUIRED, 'Le rôle de l\'utilisateur (ROLE_USER ou ROLE_ADMIN)', 'ROLE_USER')
            ->addOption('code', null, InputOption::VALUE_OPTIONAL, 'Code unique pour l\'utilisateur', null)
            ->addOption('visible', null, InputOption::VALUE_OPTIONAL, 'Si l\'utilisateur est visible', true)
            ->addOption('telephone', null, InputOption::VALUE_OPTIONAL, 'Numéro de téléphone de l\'utilisateur', null);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->getArgument('login')) {
            $login = $io->ask('Veuillez entrer un login pour le nouvel utilisateur');
            $input->setArgument('login', $login);
        }

        if (!$input->getArgument('password')) {
            $password = $io->askHidden('Veuillez entrer un mot de passe pour le nouvel utilisateur');
            $input->setArgument('password', $password);
        }

        if (!$input->getArgument('email')) {
            $email = $io->ask('Veuillez entrer une adresse email pour le nouvel utilisateur');
            $input->setArgument('email', $email);
        }

        if (!$input->getOption('role')) {
            $role = $io->choice('Veuillez choisir un rôle pour le nouvel utilisateur', ['ROLE_USER', 'ROLE_ADMIN'], 'ROLE_USER');
            $input->setOption('role', $role);
        }

        if (!$input->getOption('code')) {
            $code = $io->ask('Veuillez entrer un code unique pour l\'utilisateur (laissez vide pour générer un code aléatoire)', '');
            $input->setOption('code', $code);
        }

        if (null === $input->getOption('visible')) {
            $visible = $io->confirm('L\'utilisateur doit-il être visible ?', true);
            $input->setOption('visible', $visible);
        }

        if (!$input->getOption('telephone')) {
            $telephone = $io->ask('Veuillez entrer un numéro de téléphone pour l\'utilisateur (laissez vide pour aucun)', '');
            $input->setOption('telephone', $telephone);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $login = $input->getArgument('login');
        $plainPassword = $input->getArgument('password');
        $email = $input->getArgument('email');
        $role = $input->getOption('role');
        $code = $input->getOption('code');
        $visible = $input->getOption('visible');
        $telephone = $input->getOption('telephone');

        $existingAdmin = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['roles' => ['ROLE_ADMIN']]);

        if (!$existingAdmin) {
            $role = 'ROLE_SUPER_ADMIN';
        } elseif ($role === 'ROLE_ADMIN') {
            $io->error("Un administrateur existe déjà. Utilisez ROLE_USER pour créer un nouvel utilisateur.");
            return Command::FAILURE;
        }


        if ($this->entityManager->getRepository(Utilisateur::class)->findOneBy(['login' => $login])) {
            $io->error("Un utilisateur avec le login $login existe déjà.");
            return Command::FAILURE;
        }

        if ($this->entityManager->getRepository(Utilisateur::class)->findOneBy(['adresseEmail' => $email])) {
            $io->error("Un utilisateur avec l'adresse email $email existe déjà.");
            return Command::FAILURE;
        }

        if (!$code || !$this->utilisateurService->isCodeUnique($code)) {
            $io->error("Le code fourni est déjà utilisé ou non fourni. Un autre code sera généré.");
            $code = $this->utilisateurService->generateUniqueCode(null);
        }

        try {
            $utilisateur = new Utilisateur();
            $utilisateur->setLogin($login);
            $utilisateur->setAdresseEmail($email);
            $this->utilisateurManager->processNewUtilisateur($utilisateur, $plainPassword);
            $utilisateur->setRoles([$role]);
            $utilisateur->setCode($code);
            $utilisateur->setVisible($visible);
            $utilisateur->setTelephone($telephone);

            $this->entityManager->persist($utilisateur);
            $this->entityManager->flush();

            $io->success("Utilisateur $login créé avec succès.");
        } catch (\Exception $e) {
            $io->error("Erreur lors de la création de l'utilisateur : " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

//php bin/console app:create-user "admin" "adminN45" "admin@gmail.com" --role="ROLE_ADMIN"