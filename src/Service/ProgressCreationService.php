<?php

declare(strict_types = 1);

namespace App\Service;

use App\Entity\Progress;
use App\Exception\ProgressException;
use App\Factory\ProgressFactory;
use App\Repository\Interfaces\ProgressRepositoryInterface;

class ProgressCreationService
{
    public function __construct(
        private ValidationService $validationService,
        private PrerequisitesService $prerequisitesService,
        private ProgressStatusService $progressStatusService,
        private ProgressFactory $progressFactory,
        private ProgressRepositoryInterface $progressRepository
    ) {
    }

    public function createProgress(int $userId, int $lessonId, string $requestId, string $action = 'complete'): Progress
    {
        // Check if request_id already exists (idempotency)
        $existingByRequestId = $this->progressRepository->findByRequestId($requestId);
        if ($existingByRequestId) {
            return $this->handleExistingRequestId($existingByRequestId, $userId, $lessonId, $requestId);
        }

        // Check if progress already exists for this user/lesson
        $existingProgress = $this->progressRepository->findByUserAndLesson($userId, $lessonId);
        if ($existingProgress) {
            return $this->updateExistingProgress($existingProgress, $action, $requestId);
        }

        return $this->createNewProgress($userId, $lessonId, $requestId, $action);
    }

    private function handleExistingRequestId(Progress $existingProgress, int $userId, int $lessonId, string $requestId): Progress
    {
        // If request_id exists, check if it matches the same user/lesson
        $user = $existingProgress->getUser();
        $lesson = $existingProgress->getLesson();

        if (!$user || !$lesson) {
            throw new \InvalidArgumentException('Progress must have user and lesson');
        }

        $userIdMatches = $user->getId() === $userId;
        $lessonIdMatches = $lesson->getId() === $lessonId;

        if ($userIdMatches && $lessonIdMatches) {
            return $existingProgress; // Idempotency - return existing result
        } else {
            // Request_id exists but with different user/lesson - conflict
            throw new ProgressException(ProgressException::REQUEST_ID_CONFLICT, $requestId, $userId, $lessonId);
        }
    }

    private function updateExistingProgress(Progress $existingProgress, string $action, string $requestId): Progress
    {
        $newStatus = $this->validationService->validateAndGetStatus($action);
        if ($existingProgress->getStatus() !== $newStatus) {
            $this->progressStatusService->updateProgressStatus($existingProgress, $newStatus, $requestId);
        }

        return $existingProgress;
    }

    private function createNewProgress(int $userId, int $lessonId, string $requestId, string $action): Progress
    {
        $user = $this->validationService->validateAndGetUser($userId);
        $lesson = $this->validationService->validateAndGetLesson($lessonId);
        $this->validationService->validateEnrollment($userId, $lesson);
        $this->prerequisitesService->checkPrerequisites($userId, $lesson);
        $progressStatus = $this->validationService->validateAndGetStatus($action);

        $progress = $this->progressFactory->create($user, $lesson, $requestId, $progressStatus);
        $this->progressRepository->save($progress);

        // Dispatch event for new progress creation
        $this->progressStatusService->dispatchProgressCreatedEvent($progress, $requestId);

        return $progress;
    }

    public function isIdempotentRequest(string $requestId, int $userId, int $lessonId, string $action): bool
    {
        // Check if request_id already exists (exact idempotency)
        $existingByRequestId = $this->progressRepository->findByRequestId($requestId);
        if ($existingByRequestId) {
            return true;
        }

        // Check if this is a business-level idempotent request (same status)
        $existingProgress = $this->progressRepository->findByUserAndLesson($userId, $lessonId);
        if ($existingProgress) {
            $newStatus = $this->validationService->validateAndGetStatus($action);

            return $existingProgress->getStatus() === $newStatus; // Same status = idempotent
        }

        return false; // New progress creation
    }
}
