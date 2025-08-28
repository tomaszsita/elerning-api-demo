<?php

declare(strict_types = 1);

namespace App\Repository\Interfaces;

use App\Entity\Progress;

interface ProgressRepositoryInterface
{
    /**
     * @return Progress[]
     */
    public function findByUserAndCourse(int $userId, int $courseId): array;

    public function findByRequestId(string $requestId): ?Progress;

    public function findByUserAndLesson(int $userId, int $lessonId): ?Progress;

    public function save(Progress $progress): void;
}
