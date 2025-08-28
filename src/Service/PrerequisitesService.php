<?php

namespace App\Service;

use App\Entity\Lesson;
use App\Enum\ProgressStatus;
use App\Exception\PrerequisitesNotMetException;
use App\Repository\Interfaces\LessonRepositoryInterface;
use App\Repository\Interfaces\ProgressRepositoryInterface;

class PrerequisitesService
{
    public function __construct(
        private LessonRepositoryInterface $lessonRepository,
        private ProgressRepositoryInterface $progressRepository
    ) {
    }

    public function checkPrerequisites(int $userId, Lesson $lesson): void
    {
        $course = $lesson->getCourse();
        $currentOrderIndex = $lesson->getOrderIndex();

        $prerequisiteLessons = $this->lessonRepository->findByCourseAndOrderLessThan(
            $course->getId(),
            $currentOrderIndex
        );

        foreach ($prerequisiteLessons as $prerequisiteLesson) {
            $progress = $this->progressRepository->findByUserAndLesson($userId, $prerequisiteLesson->getId());
            
            if (!$progress || !in_array($progress->getStatus(), [ProgressStatus::COMPLETE, ProgressStatus::FAILED])) {
                throw new PrerequisitesNotMetException(
                    $userId,
                    $lesson->getId(),
                    "User {$userId} must complete lesson '{$prerequisiteLesson->getTitle()}' before accessing lesson '{$lesson->getTitle()}'"
                );
            }
        }
    }
}
