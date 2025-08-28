<?php

declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Enrollment;
use App\Repository\Interfaces\EnrollmentRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Enrollment>
 *
 * @method Enrollment|null find(int|string $id, \Doctrine\DBAL\LockMode|int|null $lockMode = null, int|null $lockVersion = null)
 * @method Enrollment|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method Enrollment[]    findAll()
 * @method Enrollment[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, int|null $limit = null, int|null $offset = null)
 */
class EnrollmentRepository extends ServiceEntityRepository implements EnrollmentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Enrollment::class);
    }

    public function existsByUserAndCourse(int $userId, int $courseId): bool
    {
        return $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.user = :userId')
            ->andWhere('e.course = :courseId')
            ->setParameter('userId', $userId)
            ->setParameter('courseId', $courseId)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * @return Enrollment[]
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.course', 'c')
            ->addSelect('c')
            ->where('e.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }
}
