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
    name: 'app:donner-admin',
    description: 'Donne le rôle ADMIN à un utilisateur via son login.'
)]
class GiveAdminCommand extends Command
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
            ->addArgument('login', InputArgument::REQUIRED, 'Le login de l\'utilisateur à promouvoir en tant qu\'admin.');
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

        $utilisateur->addRole('ROLE_ADMIN');
        $this->entityManager->flush();

        $io->success("L'utilisateur {$login} a été promu administrateur.");
        return Command::SUCCESS;
    }
}

// php bin/console app:donner-admin User
