<?php

declare(strict_types = 1);

namespace App\Repository\Interfaces;

use App\Entity\Enrollment;

interface EnrollmentRepositoryInterface
{
    public function existsByUserAndCourse(int $userId, int $courseId): bool;

    /**
     * @return Enrollment[]
     */
    public function findByUser(int $userId): array;

    public function save(Enrollment $enrollment): void;
}
