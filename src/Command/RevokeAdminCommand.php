<?php

namespace App\Command;

use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:retirer-admin',
    description: 'Retire le rôle ADMIN d\'un utilisateur via son login.'
)]
class RevokeAdminCommand extends Command
{
    private UtilisateurRepository $utilisateurRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(UtilisateurRepository $utilisateurRepository, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->utilisateurRepository = $utilisateurRepository;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('login', InputArgument::REQUIRED, 'Le login de l\'utilisateur auquel retirer le rôle admin.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $login = $input->getArgument('login');

        $utilisateur = $this->utilisateurRepository->findOneBy(['login' => $login]);
        if (!$utilisateur) {
            $io->error('Utilisateur non trouvé.');
            return Command::FAILURE;
        }

        // Vérifier si l'utilisateur a déjà le rôle admin pour le retirer
        if (!in_array('ROLE_ADMIN', $utilisateur->getRoles())) {
            $io->note('L\'utilisateur n\'a pas le rôle admin.');
            return Command::FAILURE;
        }

        $utilisateur->removeRole('ROLE_ADMIN');
        $this->entityManager->flush();

        $io->success("Le rôle admin a été retiré de l'utilisateur {$login}.");
        return Command::SUCCESS;
    }
}

// php bin/console app:retirer-admin User