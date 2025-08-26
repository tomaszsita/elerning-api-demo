<?php

namespace App\Repository;

use App\Entity\Prerequisite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Prerequisite>
 *
 * @method Prerequisite|null find(int|string $id, \Doctrine\DBAL\LockMode|int|null $lockMode = null, int|null $lockVersion = null)
 * @method Prerequisite|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method Prerequisite[] findAll()
 * @method Prerequisite[] findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, int|null $limit = null, int|null $offset = null)
 */
class PrerequisiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Prerequisite::class);
    }
}
