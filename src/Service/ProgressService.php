<?php

namespace App\Service;

use App\Entity\Progress;

class ProgressService
{
    public function __construct(
        ProgressCreationService $progressCreationService,
        ProgressStatusService $progressStatusService,
        ProgressQueryService $progressQueryService
    ) {
        $this->progressCreationService = $progressCreationService;
        $this->progressStatusService = $progressStatusService;
        $this->progressQueryService = $progressQueryService;
    }

    private ProgressCreationService $progressCreationService;
    private ProgressStatusService $progressStatusService;
    private ProgressQueryService $progressQueryService;

    public function createProgress(int $userId, int $lessonId, string $requestId, string $action = 'complete'): Progress
    {
        return $this->progressCreationService->createProgress($userId, $lessonId, $requestId, $action);
    }

    public function isIdempotentRequest(string $requestId): bool
    {
        return $this->progressCreationService->isIdempotentRequest($requestId);
    }

    /**
     * @return Progress[]
     */
    public function getUserProgress(int $userId, int $courseId): array
    {
        return $this->progressQueryService->getUserProgress($userId, $courseId);
    }

    /**
     * @return \App\Entity\ProgressHistory[]
     */
    public function getProgressHistory(int $userId, int $lessonId): array
    {
        return $this->progressQueryService->getProgressHistory($userId, $lessonId);
    }

    /**
     * @return array{completed: int, total: int, percent: int, lessons: array<int, array{id: int, status: string}>}
     */
    public function getUserProgressSummary(int $userId, int $courseId): array
    {
        return $this->progressQueryService->getUserProgressSummary($userId, $courseId);
    }

    public function deleteProgress(int $userId, int $lessonId): void
    {
        $this->progressStatusService->deleteProgress($userId, $lessonId);
    }
}
