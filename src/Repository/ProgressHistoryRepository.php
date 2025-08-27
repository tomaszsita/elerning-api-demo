<?php

namespace App\Repository;

use App\Entity\ProgressHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProgressHistory>
 *
 * @method ProgressHistory|null find(int|string $id, \Doctrine\DBAL\LockMode|int|null $lockMode = null, int|null $lockVersion = null)
 * @method ProgressHistory|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method ProgressHistory[] findAll()
 * @method ProgressHistory[] findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, int|null $limit = null, int|null $offset = null)
 */
class ProgressHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProgressHistory::class);
    }

    /**
     * @return ProgressHistory[]
     */
    public function findByUserAndLesson(int $userId, int $lessonId): array
    {
        return $this->createQueryBuilder('ph')
            ->andWhere('ph.user = :userId')
            ->andWhere('ph.lesson = :lessonId')
            ->setParameter('userId', $userId)
            ->setParameter('lessonId', $lessonId)
            ->orderBy('ph.changedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ProgressHistory[]
     */
    public function findByProgress(int $progressId): array
    {
        return $this->createQueryBuilder('ph')
            ->andWhere('ph.progress = :progressId')
            ->setParameter('progressId', $progressId)
            ->orderBy('ph.changedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
