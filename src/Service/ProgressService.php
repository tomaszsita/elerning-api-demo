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
use App\Repository\UserRepository;
use App\Repository\LessonRepository;
use App\Repository\ProgressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProgressService
{
    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        LessonRepository $lessonRepository,
        ProgressRepository $progressRepository,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->lessonRepository = $lessonRepository;
        $this->progressRepository = $progressRepository;
        $this->eventDispatcher = $eventDispatcher;
        

    }

    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private LessonRepository $lessonRepository;
    private ProgressRepository $progressRepository;
    private EventDispatcherInterface $eventDispatcher;

    public function createProgress(int $userId, int $lessonId, string $requestId, string $status = 'complete'): Progress
    {
        // IDEMPOTENCY: Check if progress with this request_id already exists
        $existingProgress = $this->progressRepository->findByRequestId($requestId);
        if ($existingProgress) {
            return $existingProgress; // Idempotency - return existing
        }

        // Check if user exists
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new UserNotFoundException($userId);
        }

        // Check if lesson exists
        $lesson = $this->lessonRepository->find($lessonId);
        if (!$lesson) {
            throw new LessonNotFoundException($lessonId);
        }

        // Check if user is enrolled in the course of this lesson
        $enrollmentRepository = $this->entityManager->getRepository(\App\Entity\Enrollment::class);
        if (!$enrollmentRepository->existsByUserAndCourse($userId, $lesson->getCourse()->getId())) {
            throw new \App\Exception\UserNotEnrolledException($userId, $lesson->getCourse()->getId());
        }

        // Check prerequisites - user must complete all previous lessons
        $this->checkPrerequisites($userId, $lesson);

        // Check if status is valid
        try {
            $progressStatus = ProgressStatus::fromString($status);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidStatusTransitionException('', $status);
        }

        // Create new progress
        $progress = new Progress();
        $progress->setUser($user);
        $progress->setLesson($lesson);
        $progress->setRequestId($requestId);
        $progress->setStatus($progressStatus);

        if ($status === 'complete') {
            $progress->setCompletedAt(new \DateTimeImmutable());
        }

        $this->entityManager->persist($progress);
        $this->entityManager->flush();

        // Dispatch event
        if ($status === 'complete') {
            $this->eventDispatcher->dispatch(
                new \App\Event\ProgressCompletedEvent($progress),
                'progress.completed'
            );
        }

        return $progress;
    }

    public function updateProgressStatus(int $progressId, string $newStatus): Progress
    {
        $progress = $this->progressRepository->find($progressId);
        if (!$progress) {
            throw new \App\Exception\ProgressNotFoundException($progressId);
        }

        $currentStatus = $progress->getStatus();

        // Check if transition is allowed
        $newStatusEnum = ProgressStatus::fromString($newStatus);
        if (!ProgressStatus::canTransition($currentStatus, $newStatusEnum)) {
            throw new InvalidStatusTransitionException($currentStatus ? $currentStatus->name : '', $newStatus);
        }

        // Update status
        $progress->setStatus($newStatusEnum);

        if ($newStatus === 'complete') {
            $progress->setCompletedAt(new \DateTimeImmutable());
        }

        $this->entityManager->flush();

        // Dispatch event
        if ($newStatus === 'complete') {
            $this->eventDispatcher->dispatch(
                new \App\Event\ProgressCompletedEvent($progress),
                'progress.completed'
            );
        }

        return $progress;
    }

    public function getUserProgress(int $userId, int $courseId): array
    {
        // Check if user exists
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new UserNotFoundException($userId);
        }

        return $this->progressRepository->findByUserAndCourse($userId, $courseId);
    }

    public function getProgressByRequestId(string $requestId): ?Progress
    {
        return $this->progressRepository->findByRequestId($requestId);
    }

    private function checkPrerequisites(int $userId, Lesson $lesson): void
    {
        $course = $lesson->getCourse();
        $currentOrderIndex = $lesson->getOrderIndex();

        // Get all lessons with lower orderIndex in the same course
        $prerequisiteLessons = $this->lessonRepository->findByCourseAndOrderLessThan(
            $course->getId(),
            $currentOrderIndex
        );

        // Check if user completed all previous lessons
        foreach ($prerequisiteLessons as $prerequisiteLesson) {
            $progress = $this->progressRepository->findByUserAndLesson($userId, $prerequisiteLesson->getId());
            
            if (!$progress || $progress->getStatus() !== 'complete') {
                throw new PrerequisitesNotMetException(
                    $userId,
                    $lesson->getId(),
                    "User {$userId} must complete lesson '{$prerequisiteLesson->getTitle()}' before accessing lesson '{$lesson->getTitle()}'"
                );
            }
        }
    }
}
