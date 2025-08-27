<?php

namespace App\Repository\Interfaces;

use App\Entity\Course;

interface CourseRepositoryInterface
{
    public function countEnrollmentsByCourse(int $courseId): int;
    public function save(Course $course): void;

    /**
     * @return Course[]
     */
    public function findAllWithRemainingSeats(): array;

    /**
     * @return Course[]
     */
    public function findByUser(int $userId): array;
}
