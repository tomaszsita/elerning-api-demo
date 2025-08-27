<?php

namespace App\Repository;

use App\Entity\Course;
use App\Repository\Interfaces\CourseRepositoryInterface;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Course>
 *
 * @method Course|null find(int|string $id, \Doctrine\DBAL\LockMode|int|null $lockMode = null, int|null $lockVersion = null)
 * @method Course|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method Course[] findAll()
 * @method Course[] findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, int|null $limit = null, int|null $offset = null)
 */
class CourseRepository extends ServiceEntityRepository implements CourseRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }

    public function countEnrollmentsByCourse(int $courseId): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(e.id)')
            ->leftJoin('c.enrollments', 'e')
            ->where('c.id = :courseId')
            ->setParameter('courseId', $courseId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(Course $course): void
    {
        $this->getEntityManager()->persist($course);
        $this->getEntityManager()->flush();
    }

    /**
     * @return Course[]
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.enrollments', 'e')
            ->where('e.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Course[]
     */
    public function findAllWithRemainingSeats(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c')
            ->leftJoin('c.enrollments', 'e')
            ->groupBy('c.id')
            ->having('COUNT(e.id) < c.maxSeats')
            ->getQuery()
            ->getResult();
    }
}
