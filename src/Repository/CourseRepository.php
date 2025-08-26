<?php

namespace App\Repository;

use App\Entity\Course;
use App\Repository\Interfaces\CourseRepositoryInterface;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Course>
 *
 * @method Course|null find($id, $lockMode = null, $lockVersion = null)
 * @method Course|null findOneBy(array $criteria, array $orderBy = null)
 * @method Course[]    findAll()
 * @method Course[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
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
        $this->_em->persist($course);
        $this->_em->flush();
    }
}
