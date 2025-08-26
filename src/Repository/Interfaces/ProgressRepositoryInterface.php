<?php

namespace App\Repository\Interfaces;

use App\Entity\Progress;

interface ProgressRepositoryInterface
{
    public function find(mixed $id, $lockMode = null, ?int $lockVersion = null): ?object;
    public function findByUserAndCourse(int $userId, int $courseId): array;
    public function findByRequestId(string $requestId): ?Progress;
    public function save(Progress $progress): void;
}
