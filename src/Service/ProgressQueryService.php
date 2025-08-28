<?php

declare(strict_types = 1);

namespace App\Service;

use App\Repository\Interfaces\ProgressRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class ProgressQueryService
{
    public function __construct(
        private ValidationService $validationService,
        private ProgressRepositoryInterface $progressRepository,
        private EntityManagerInterface $entityManager
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

        /** @var \App\Repository\ProgressHistoryRepository $repository */
        $repository = $this->entityManager->getRepository(\App\Entity\ProgressHistory::class);

        return $repository->findByUserAndLesson($userId, $lessonId);
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
        $completedLessons = count(array_filter($progressList, fn ($p) => 'complete' === $p->getStatus()->value));
        $percent = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        $lessonsData = [];
        foreach ($course->getLessons() as $lesson) {
            $progress = array_filter($progressList, fn ($p) => $p->getLesson()->getId() === $lesson->getId());
            $status = empty($progress) ? 'pending' : reset($progress)->getStatus()->value;

            $lessonsData[] = [
                'id'     => $lesson->getId(),
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
