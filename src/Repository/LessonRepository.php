<?php

namespace App\Repository;

use App\Entity\Lesson;
use App\Repository\Interfaces\LessonRepositoryInterface;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Lesson>
 *
 * @method Lesson|null find($id, $lockMode = null, $lockVersion = null)
 * @method Lesson|null findOneBy(array $criteria, array $orderBy = null)
 * @method Lesson[]    findAll()
 * @method Lesson[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LessonRepository extends ServiceEntityRepository implements LessonRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lesson::class);
    }

    public function save(Lesson $lesson): void
    {
        $this->_em->persist($lesson);
        $this->_em->flush();
    }

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
