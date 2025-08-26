<?php

namespace App\Repository\Interfaces;

use App\Entity\Enrollment;

interface EnrollmentRepositoryInterface
{
    public function existsByUserAndCourse(int $userId, int $courseId): bool;
    public function findByUser(int $userId): array;
    public function save(Enrollment $enrollment): void;
}
