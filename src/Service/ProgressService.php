<?php

namespace App\Service;

use App\Entity\Progress;
use App\Entity\User;
use App\Entity\Lesson;
use App\Enum\ProgressStatus;
use App\Exception\InvalidStatusTransitionException;
use App\Exception\UserNotFoundException;
use App\Exception\LessonNotFoundException;
use App\Exception\PrerequisitesNotMetException;
use App\Repository\Interfaces\UserRepositoryInterface;
use App\Repository\Interfaces\LessonRepositoryInterface;
use App\Repository\Interfaces\ProgressRepositoryInterface;
use App\Repository\Interfaces\EnrollmentRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProgressService
{
    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepositoryInterface $userRepository,
        LessonRepositoryInterface $lessonRepository,
        ProgressRepositoryInterface $progressRepository,
        EnrollmentRepositoryInterface $enrollmentRepository,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->lessonRepository = $lessonRepository;
        $this->progressRepository = $progressRepository;
        $this->enrollmentRepository = $enrollmentRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    private EntityManagerInterface $entityManager;
    private UserRepositoryInterface $userRepository;
    private LessonRepositoryInterface $lessonRepository;
    private ProgressRepositoryInterface $progressRepository;
    private EnrollmentRepositoryInterface $enrollmentRepository;
    private EventDispatcherInterface $eventDispatcher;

    public function createProgress(int $userId, int $lessonId, string $requestId, string $status = 'complete'): Progress
    {
        $existingProgress = $this->progressRepository->findByRequestId($requestId);
        if ($existingProgress) {
            return $existingProgress;
        }

        $user = $this->validateAndGetUser($userId);
        $lesson = $this->validateAndGetLesson($lessonId);
        $this->validateEnrollment($userId, $lesson);
        $this->checkPrerequisites($userId, $lesson);
        $progressStatus = $this->validateAndGetStatus($status);

        $progress = $this->createProgressEntity($user, $lesson, $requestId, $progressStatus);
        $this->saveProgress($progress);
        $this->dispatchCompletionEventIfNeeded($progress, $status);

        return $progress;
    }

    public function updateProgressStatus(int $progressId, string $newStatus): Progress
    {
        $progress = $this->validateAndGetProgress($progressId);
        $newStatusEnum = $this->validateAndGetStatus($newStatus);
        
        $this->validateStatusTransition($progress->getStatus(), $newStatusEnum);
        $this->updateProgressEntity($progress, $newStatusEnum);
        $this->saveProgress($progress);
        $this->dispatchCompletionEventIfNeeded($progress, $newStatus);

        return $progress;
    }

    public function getUserProgress(int $userId, int $courseId): array
    {
        $this->validateAndGetUser($userId);
        return $this->progressRepository->findByUserAndCourse($userId, $courseId);
    }

    public function getProgressByRequestId(string $requestId): ?Progress
    {
        return $this->progressRepository->findByRequestId($requestId);
    }

    private function validateAndGetUser(int $userId): User
    {
        $user = $this->entityManager->find(User::class, $userId);
        if (!$user) {
            throw new UserNotFoundException($userId);
        }
        return $user;
    }

    private function validateAndGetLesson(int $lessonId): Lesson
    {
        $lesson = $this->entityManager->find(Lesson::class, $lessonId);
        if (!$lesson) {
            throw new LessonNotFoundException($lessonId);
        }
        return $lesson;
    }

    private function validateAndGetProgress(int $progressId): Progress
    {
        $progress = $this->entityManager->find(Progress::class, $progressId);
        if (!$progress) {
            throw new \App\Exception\ProgressNotFoundException($progressId);
        }
        return $progress;
    }

    private function validateEnrollment(int $userId, Lesson $lesson): void
    {
        if (!$this->enrollmentRepository->existsByUserAndCourse($userId, $lesson->getCourse()->getId())) {
            throw new \App\Exception\UserNotEnrolledException($userId, $lesson->getCourse()->getId());
        }
    }

    private function validateAndGetStatus(string $status): ProgressStatus
    {
        try {
            return ProgressStatus::fromString($status);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidStatusTransitionException('', $status);
        }
    }

    private function validateStatusTransition(?ProgressStatus $currentStatus, ProgressStatus $newStatus): void
    {
        if (!ProgressStatus::canTransition($currentStatus, $newStatus)) {
            throw new InvalidStatusTransitionException(
                $currentStatus ? $currentStatus->name : '', 
                $newStatus->value
            );
        }
    }

    private function createProgressEntity(User $user, Lesson $lesson, string $requestId, ProgressStatus $status): Progress
    {
        $progress = new Progress();
        $progress->setUser($user);
        $progress->setLesson($lesson);
        $progress->setRequestId($requestId);
        $progress->setStatus($status);

        if ($status === ProgressStatus::COMPLETE) {
            $progress->setCompletedAt(new \DateTimeImmutable());
        }

        return $progress;
    }

    private function updateProgressEntity(Progress $progress, ProgressStatus $newStatus): void
    {
        $progress->setStatus($newStatus);

        if ($newStatus === ProgressStatus::COMPLETE) {
            $progress->setCompletedAt(new \DateTimeImmutable());
        }
    }

    private function saveProgress(Progress $progress): void
    {
        $this->entityManager->persist($progress);
        $this->entityManager->flush();
    }

    private function dispatchCompletionEventIfNeeded(Progress $progress, string $status): void
    {
        if ($status === 'complete') {
            $this->eventDispatcher->dispatch(
                new \App\Event\ProgressCompletedEvent($progress),
                'progress.completed'
            );
        }
    }

    private function checkPrerequisites(int $userId, Lesson $lesson): void
    {
        $course = $lesson->getCourse();
        $currentOrderIndex = $lesson->getOrderIndex();

        $prerequisiteLessons = $this->lessonRepository->findByCourseAndOrderLessThan(
            $course->getId(),
            $currentOrderIndex
        );

        foreach ($prerequisiteLessons as $prerequisiteLesson) {
            $progress = $this->progressRepository->findByUserAndLesson($userId, $prerequisiteLesson->getId());
            
            if (!$progress || $progress->getStatus() !== ProgressStatus::COMPLETE) {
                throw new PrerequisitesNotMetException(
                    $userId,
                    $lesson->getId(),
                    "User {$userId} must complete lesson '{$prerequisiteLesson->getTitle()}' before accessing lesson '{$lesson->getTitle()}'"
                );
            }
        }
    }
}
