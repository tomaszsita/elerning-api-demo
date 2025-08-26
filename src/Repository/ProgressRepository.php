<?php

namespace App\Repository;

use App\Entity\Progress;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Progress>
 *
 * @method Progress|null find($id, $lockMode = null, $lockVersion = null)
 * @method Progress|null findOneBy(array $criteria, array $orderBy = null)
 * @method Progress[]    findAll()
 * @method Progress[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Progress::class);
    }

    public function findByUserAndCourse(int $userId, int $courseId): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.lesson', 'l')
            ->addSelect('l')
            ->where('p.user = :userId')
            ->andWhere('l.course = :courseId')
            ->setParameter('userId', $userId)
            ->setParameter('courseId', $courseId)
            ->orderBy('l.orderIndex', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByRequestId(string $requestId): ?Progress
    {
        return $this->findOneBy(['requestId' => $requestId]);
    }

    public function save(Progress $progress): void
    {
        $this->_em->persist($progress);
        $this->_em->flush();
    }

    public function findByUserAndLesson(int $userId, int $lessonId): ?Progress
    {
        return $this->findOneBy([
            'user' => $userId,
            'lesson' => $lessonId
        ]);
    }
}
