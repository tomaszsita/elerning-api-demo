<?php

declare(strict_types = 1);

namespace App\Service;

use App\Repository\Interfaces\ProgressHistoryRepositoryInterface;
use App\Repository\Interfaces\ProgressRepositoryInterface;

class ProgressQueryService
{
    public function __construct(
        private ValidationService $validationService,
        private ProgressRepositoryInterface $progressRepository,
        private ProgressHistoryRepositoryInterface $progressHistoryRepository
    ) {
    }

    /**
     * @return \App\Entity\Progress[]
     */
    public function getUserProgress(int $userId, int $courseId): array
    {
        $this->validationService->validateAndGetUser($userId);

        return $this->progressRepository->findByUserAndCourse($userId, $courseId);
    }

    /**
     * @return \App\Entity\ProgressHistory[]
     */
    public function getProgressHistory(int $userId, int $lessonId): array
    {
        $this->validationService->validateAndGetUser($userId);
        $this->validationService->validateAndGetLesson($lessonId);

        return $this->progressHistoryRepository->findByUserAndLesson($userId, $lessonId);
    }

    /**
     * @return array{completed: int, total: int, percent: int, lessons: array<int, array{id: int, status: string}>}
     */
    public function getUserProgressSummary(int $userId, int $courseId): array
    {
        $this->validationService->validateAndGetUser($userId);
        $course = $this->validationService->validateAndGetCourse($courseId);

        $progressList = $this->getUserProgress($userId, $courseId);
        $totalLessons = count($course->getLessons());
        $completedLessons = count(array_filter($progressList, function ($p) {
            $status = $p->getStatus();

            return $status && 'complete' === $status->value;
        }));
        $percent = $totalLessons > 0 ? (int) round(($completedLessons / $totalLessons) * 100) : 0;

        $lessonsData = [];
        foreach ($course->getLessons() as $lesson) {
            $progress = array_filter($progressList, function ($p) use ($lesson) {
                $progressLesson = $p->getLesson();

                return $progressLesson && $progressLesson->getId() === $lesson->getId();
            });
            $status = 'pending';
            if (!empty($progress)) {
                $progressStatus = reset($progress)->getStatus();
                if ($progressStatus) {
                    $status = $progressStatus->value;
                }
            }

            $lessonId = $lesson->getId();
            if (!$lessonId) {
                throw new \InvalidArgumentException('Lesson must have an ID');
            }

            $lessonsData[] = [
                'id'     => $lessonId,
                'status' => $status,
            ];
        }

        return [
            'completed' => $completedLessons,
            'total'     => $totalLessons,
            'percent'   => $percent,
            'lessons'   => $lessonsData,
        ];
    }
}
