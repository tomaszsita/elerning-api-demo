<?php

namespace App\Service;

use App\Entity\Progress;
use App\Event\ProgressChangedEvent;
use App\Factory\ProgressFactory;
use App\Factory\ProgressChangedEventFactory;
use App\Repository\Interfaces\ProgressRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
class ProgressService
{
    public function __construct(
        ValidationService $validationService,
        PrerequisitesService $prerequisitesService,
        ProgressFactory $progressFactory,
        ProgressRepositoryInterface $progressRepository,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher,
        ProgressChangedEventFactory $progressChangedEventFactory
    ) {
        $this->validationService = $validationService;
        $this->prerequisitesService = $prerequisitesService;
        $this->progressFactory = $progressFactory;
        $this->progressRepository = $progressRepository;
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->progressChangedEventFactory = $progressChangedEventFactory;
    }

    private ValidationService $validationService;
    private PrerequisitesService $prerequisitesService;
    private ProgressFactory $progressFactory;
    private ProgressRepositoryInterface $progressRepository;
    private EntityManagerInterface $entityManager;
    private EventDispatcherInterface $eventDispatcher;
    private ProgressChangedEventFactory $progressChangedEventFactory;

    public function createProgress(int $userId, int $lessonId, string $requestId, string $action = 'complete'): Progress
    {
        // Check if progress already exists for this user/lesson
        $existingProgress = $this->progressRepository->findByUserAndLesson($userId, $lessonId);
        if ($existingProgress) {
            // Update status if different
            $newStatus = $this->validationService->validateAndGetStatus($action);
            if ($existingProgress->getStatus() !== $newStatus) {
                $this->updateProgressStatus($existingProgress, $newStatus, $requestId);
            }
            return $existingProgress;
        }

        // Check if request_id already exists (idempotency)
        $existingByRequestId = $this->progressRepository->findByRequestId($requestId);
        if ($existingByRequestId) {
            return $existingByRequestId;
        }

        $user = $this->validationService->validateAndGetUser($userId);
        $lesson = $this->validationService->validateAndGetLesson($lessonId);
        $this->validationService->validateEnrollment($userId, $lesson);
        $this->prerequisitesService->checkPrerequisites($userId, $lesson);
        $progressStatus = $this->validationService->validateAndGetStatus($action);

        $progress = $this->progressFactory->create($user, $lesson, $requestId, $progressStatus);
        $this->saveProgress($progress);

        return $progress;
    }

    /**
     * @return Progress[]
     */
    public function getUserProgress(int $userId, int $courseId): array
    {
        $this->validationService->validateAndGetUser($userId);
        return $this->progressRepository->findByUserAndCourse($userId, $courseId);
    }

    private function saveProgress(Progress $progress): void
    {
        $this->entityManager->persist($progress);
        $this->entityManager->flush();
    }

    private function updateProgressStatus(Progress $progress, \App\Enum\ProgressStatus $newStatus, ?string $requestId = null): void
    {
        $currentStatus = $progress->getStatus();
        $oldStatus = $currentStatus ? $currentStatus->value : null;
        
        $progress->setStatus($newStatus);
        
        if ($newStatus === \App\Enum\ProgressStatus::COMPLETE) {
            $progress->setCompletedAt(new \DateTimeImmutable());
        } elseif ($newStatus === \App\Enum\ProgressStatus::PENDING) {
            $progress->setCompletedAt(null);
        }
        
        $this->entityManager->flush();

        // Dispatch event for history tracking
        $event = $this->progressChangedEventFactory->create($progress, $oldStatus, $newStatus->value, $requestId);
        $this->eventDispatcher->dispatch($event, ProgressChangedEvent::NAME);
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
        $completedLessons = count(array_filter($progressList, fn($p) => $p->getStatus()->value === 'complete'));
        $percent = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        $lessonsData = [];
        foreach ($course->getLessons() as $lesson) {
            $progress = array_filter($progressList, fn($p) => $p->getLesson()->getId() === $lesson->getId());
            $status = empty($progress) ? 'pending' : reset($progress)->getStatus()->value;
            
            $lessonsData[] = [
                'id' => $lesson->getId(),
                'status' => $status
            ];
        }

        return [
            'completed' => $completedLessons,
            'total' => $totalLessons,
            'percent' => $percent,
            'lessons' => $lessonsData
        ];
    }

    public function deleteProgress(int $userId, int $lessonId): void
    {
        $progress = $this->progressRepository->findByUserAndLesson($userId, $lessonId);
        if ($progress && in_array($progress->getStatus(), [\App\Enum\ProgressStatus::COMPLETE, \App\Enum\ProgressStatus::FAILED])) {
            // Reset to pending instead of deleting - preserves audit trail
            $this->updateProgressStatus($progress, \App\Enum\ProgressStatus::PENDING);
        }
    }
}
