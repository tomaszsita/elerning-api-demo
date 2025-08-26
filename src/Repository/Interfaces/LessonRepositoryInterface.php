<?php

namespace App\Repository\Interfaces;

use App\Entity\Lesson;

interface LessonRepositoryInterface
{
    public function findByCourseAndOrderLessThan(int $courseId, int $orderIndex): array;
    public function save(Lesson $lesson): void;
}
