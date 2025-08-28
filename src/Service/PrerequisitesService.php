<?php

declare(strict_types = 1);

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
        if (!$course) {
            throw new \InvalidArgumentException('Lesson must have a course');
        }
        
        $currentOrderIndex = $lesson->getOrderIndex();
        if ($currentOrderIndex === null) {
            throw new \InvalidArgumentException('Lesson must have an order index');
        }

        $courseId = $course->getId();
        if (!$courseId) {
            throw new \InvalidArgumentException('Course must have an ID');
        }
        
        $prerequisiteLessons = $this->lessonRepository->findByCourseAndOrderLessThan(
            $courseId,
            $currentOrderIndex
        );

        foreach ($prerequisiteLessons as $prerequisiteLesson) {
            $lessonId = $prerequisiteLesson->getId();
            if (!$lessonId) {
                throw new \InvalidArgumentException('Prerequisite lesson must have an ID');
            }
            
            $progress = $this->progressRepository->findByUserAndLesson($userId, $lessonId);

            if (!$progress || !in_array($progress->getStatus(), [ProgressStatus::COMPLETE, ProgressStatus::FAILED])) {
                $currentLessonId = $lesson->getId();
                if (!$currentLessonId) {
                    throw new \InvalidArgumentException('Current lesson must have an ID');
                }
                
                throw new PrerequisitesNotMetException($userId, $currentLessonId, "User {$userId} must complete lesson '{$prerequisiteLesson->getTitle()}' before accessing lesson '{$lesson->getTitle()}'");
            }
        }
    }
}
