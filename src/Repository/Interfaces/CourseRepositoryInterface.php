<?php

namespace App\Repository\Interfaces;

use App\Entity\Course;

interface CourseRepositoryInterface
{
    public function find(mixed $id, $lockMode = null, ?int $lockVersion = null): ?object;
    public function countEnrollmentsByCourse(int $courseId): int;
    public function save(Course $course): void;
}
