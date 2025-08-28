<?php

declare(strict_types = 1);

namespace App\Service;

use App\Entity\Progress;
use App\Exception\ProgressException;
use App\Factory\ProgressFactory;
use App\Repository\Interfaces\ProgressRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class ProgressCreationService
{
    public function __construct(
        private ValidationService $validationService,
        private PrerequisitesService $prerequisitesService,
        private ProgressFactory $progressFactory,
        private ProgressRepositoryInterface $progressRepository,
        private EntityManagerInterface $entityManager
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
        if ($existingProgress->getUser()->getId() === $userId
            && $existingProgress->getLesson()->getId() === $lessonId) {
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
            $existingProgress->setStatus($newStatus);
            $existingProgress->setRequestId($requestId);

            if (\App\Enum\ProgressStatus::COMPLETE === $newStatus) {
                $existingProgress->setCompletedAt(new \DateTimeImmutable());
            } elseif (\App\Enum\ProgressStatus::PENDING === $newStatus) {
                $existingProgress->setCompletedAt(null);
            }

            $this->entityManager->flush();
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
        $this->saveProgress($progress);

        return $progress;
    }

    private function saveProgress(Progress $progress): void
    {
        $this->entityManager->persist($progress);
        $this->entityManager->flush();
    }

    public function isIdempotentRequest(string $requestId): bool
    {
        return null !== $this->progressRepository->findByRequestId($requestId);
    }
}
