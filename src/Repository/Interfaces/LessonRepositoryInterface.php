<?php

declare(strict_types = 1);

namespace App\Repository\Interfaces;

use App\Entity\Lesson;

interface LessonRepositoryInterface
{
    /**
     * @return Lesson[]
     */
    public function findByCourseAndOrderLessThan(int $courseId, int $orderIndex): array;
}
