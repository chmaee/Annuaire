<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UtilisateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    /**
     * @return Utilisateur[] Returns an array of Utilisateur objects
     */
    public function findVisibleUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.visible = :visible')
            ->setParameter('visible', true)
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCode(string $code): ?Utilisateur
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function isCodeUnique(string $code): bool
    {
        return $this->createQueryBuilder('u')
                ->andWhere('u.code = :code')
                ->setParameter('code', $code)
                ->getQuery()
                ->getOneOrNullResult() === null;
    }

    public function isCodeUniqueForExistingUser(string $code, string $login): bool
    {
        return $this->createQueryBuilder('u')
                ->where('u.code = :code')
                ->andWhere('u.login != :login')
                ->setParameter('code', $code)
                ->setParameter('login', $login)
                ->getQuery()
                ->getOneOrNullResult() === null;
    }

}
