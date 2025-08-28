<?php

declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Lesson;
use App\Repository\Interfaces\LessonRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Lesson>
 *
 * @method Lesson|null find(int|string $id, \Doctrine\DBAL\LockMode|int|null $lockMode = null, int|null $lockVersion = null)
 * @method Lesson|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method Lesson[]    findAll()
 * @method Lesson[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, int|null $limit = null, int|null $offset = null)
 */
class LessonRepository extends ServiceEntityRepository implements LessonRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lesson::class);
    }

    /**
     * @return Lesson[]
     */
    public function findByCourseAndOrderLessThan(int $courseId, int $orderIndex): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.course = :courseId')
            ->andWhere('l.orderIndex < :orderIndex')
            ->setParameter('courseId', $courseId)
            ->setParameter('orderIndex', $orderIndex)
            ->orderBy('l.orderIndex', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
