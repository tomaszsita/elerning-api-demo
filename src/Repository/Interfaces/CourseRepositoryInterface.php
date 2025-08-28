<?php

declare(strict_types = 1);

namespace App\Repository\Interfaces;

use App\Entity\Course;

interface CourseRepositoryInterface
{
    public function countEnrollmentsByCourse(int $courseId): int;

    /**
     * @return Course[]
     */
    public function findAllWithRemainingSeats(): array;
}
