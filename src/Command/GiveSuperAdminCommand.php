<?php

namespace App\Command;

use App\Repository\UtilisateurRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'app:donner-super-admin',
    description: 'Donne le rôle SUPER_ADMIN à un utilisateur via son login.'
)]
class GiveSuperAdminCommand extends Command
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
            ->addArgument('login', InputArgument::REQUIRED, 'Le login de l\'utilisateur à promouvoir en tant que super-admin.');
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

        $utilisateur->addRole('ROLE_SUPER_ADMIN');
        $this->entityManager->flush();

        $io->success("L'utilisateur {$login} a été promu super-administrateur.");
        return Command::SUCCESS;
    }
}

// php bin/console app:donner-super-admin User
