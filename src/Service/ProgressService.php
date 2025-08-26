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

        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new UserNotFoundException($userId);
        }

        $lesson = $this->lessonRepository->find($lessonId);
        if (!$lesson) {
            throw new LessonNotFoundException($lessonId);
        }

        if (!$this->enrollmentRepository->existsByUserAndCourse($userId, $lesson->getCourse()->getId())) {
            throw new \App\Exception\UserNotEnrolledException($userId, $lesson->getCourse()->getId());
        }

        $this->checkPrerequisites($userId, $lesson);

        try {
            $progressStatus = ProgressStatus::fromString($status);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidStatusTransitionException('', $status);
        }

        $progress = new Progress();
        $progress->setUser($user);
        $progress->setLesson($lesson);
        $progress->setRequestId($requestId);
        $progress->setStatus($progressStatus);

        $completedAt = null;
        if ($status === 'complete') {
            $completedAt = new \DateTimeImmutable();
            $progress->setCompletedAt($completedAt);
        }

        $this->entityManager->persist($progress);
        $this->entityManager->flush();

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

        $newStatusEnum = ProgressStatus::fromString($newStatus);
        if (!ProgressStatus::canTransition($currentStatus, $newStatusEnum)) {
            throw new InvalidStatusTransitionException($currentStatus ? $currentStatus->name : '', $newStatus);
        }

        $progress->setStatus($newStatusEnum);

        if ($newStatus === 'complete') {
            $progress->setCompletedAt(new \DateTimeImmutable());
        }

        $this->entityManager->flush();

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

        $prerequisiteLessons = $this->lessonRepository->findByCourseAndOrderLessThan(
            $course->getId(),
            $currentOrderIndex
        );

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
