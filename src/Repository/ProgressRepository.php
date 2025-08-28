<?php

declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Progress;
use App\Repository\Interfaces\ProgressRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Progress>
 *
 * @method Progress|null find(int|string $id, \Doctrine\DBAL\LockMode|int|null $lockMode = null, int|null $lockVersion = null)
 * @method Progress|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method Progress[]    findAll()
 * @method Progress[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, int|null $limit = null, int|null $offset = null)
 */
class ProgressRepository extends ServiceEntityRepository implements ProgressRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Progress::class);
    }

    /**
     * @return Progress[]
     */
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
        $this->getEntityManager()->persist($progress);
        $this->getEntityManager()->flush();
    }

    public function findByUserAndLesson(int $userId, int $lessonId): ?Progress
    {
        return $this->findOneBy([
            'user'   => $userId,
            'lesson' => $lessonId,
        ]);
    }
}
