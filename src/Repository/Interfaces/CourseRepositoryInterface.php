<?php

namespace App\Repository\Interfaces;

use App\Entity\Course;

interface CourseRepositoryInterface
{
    public function find(int $id): ?Course;
    public function countEnrollmentsByCourse(int $courseId): int;
    public function save(Course $course): void;
}
