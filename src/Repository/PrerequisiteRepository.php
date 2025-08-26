<?php

namespace App\Repository;

use App\Entity\Prerequisite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Prerequisite>
 *
 * @method Prerequisite|null find($id, $lockMode = null, $lockVersion = null)
 * @method Prerequisite|null findOneBy(array $criteria, array $orderBy = null)
 * @method Prerequisite[]    findAll()
 * @method Prerequisite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrerequisiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Prerequisite::class);
    }
}
