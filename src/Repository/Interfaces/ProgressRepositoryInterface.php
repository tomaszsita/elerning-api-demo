<?php

namespace App\Repository\Interfaces;

use App\Entity\Progress;

interface ProgressRepositoryInterface
{
    public function findByUserAndCourse(int $userId, int $courseId): array;
    public function findByRequestId(string $requestId): ?Progress;
    public function findByUserAndLesson(int $userId, int $lessonId): ?Progress;
    public function save(Progress $progress): void;
}
