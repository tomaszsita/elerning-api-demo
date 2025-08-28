<?php

declare(strict_types = 1);

namespace App\Repository\Interfaces;

use App\Entity\ProgressHistory;

interface ProgressHistoryRepositoryInterface
{
    /**
     * @return ProgressHistory[]
     */
    public function findByUserAndLesson(int $userId, int $lessonId): array;
}
