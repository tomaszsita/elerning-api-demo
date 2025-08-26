<?php

namespace App\Service;

use App\Entity\Progress;
use App\Entity\User;
use App\Entity\Lesson;
use App\Enum\ProgressStatus;
use App\Exception\InvalidStatusTransitionException;
use App\Exception\UserNotFoundException;
use App\Exception\LessonNotFoundException;
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
        EnrollmentService $enrollmentService,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->lessonRepository = $lessonRepository;
        $this->progressRepository = $progressRepository;
        $this->enrollmentService = $enrollmentService;
        $this->eventDispatcher = $eventDispatcher;
    }

    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private LessonRepository $lessonRepository;
    private ProgressRepository $progressRepository;
    private EnrollmentService $enrollmentService;
    private EventDispatcherInterface $eventDispatcher;

    public function createProgress(int $userId, int $lessonId, string $requestId, string $status = ProgressStatus::COMPLETE): Progress
    {
        // IDEMPOTENCY: Sprawdź czy już istnieje progress z tym request_id
        $existingProgress = $this->progressRepository->findByRequestId($requestId);
        if ($existingProgress) {
            return $existingProgress; // Idempotency - zwróć istniejący
        }

        // Sprawdź czy użytkownik istnieje
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new UserNotFoundException($userId);
        }

        // Sprawdź czy lekcja istnieje
        $lesson = $this->lessonRepository->find($lessonId);
        if (!$lesson) {
            throw new LessonNotFoundException($lessonId);
        }

        // Sprawdź czy użytkownik jest zapisany na kurs tej lekcji
        if (!$this->enrollmentService->isUserEnrolled($userId, $lesson->getCourse()->getId())) {
            throw new \App\Exception\UserNotEnrolledException($userId, $lesson->getCourse()->getId());
        }

        // Sprawdź czy status jest prawidłowy
        if (!ProgressStatus::isValid($status)) {
            throw new InvalidStatusTransitionException('', $status);
        }

        // Utwórz nowy progress
        $progress = new Progress();
        $progress->setUser($user);
        $progress->setLesson($lesson);
        $progress->setRequestId($requestId);
        $progress->setStatus($status);

        if ($status === ProgressStatus::COMPLETE) {
            $progress->setCompletedAt(new \DateTimeImmutable());
        }

        $this->entityManager->persist($progress);
        $this->entityManager->flush();

        // Wyślij event
        if ($status === ProgressStatus::COMPLETE) {
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

        // Sprawdź czy przejście jest dozwolone
        if (!ProgressStatus::canTransition($currentStatus, $newStatus)) {
            throw new InvalidStatusTransitionException($currentStatus, $newStatus);
        }

        // Aktualizuj status
        $progress->setStatus($newStatus);

        if ($newStatus === ProgressStatus::COMPLETE) {
            $progress->setCompletedAt(new \DateTimeImmutable());
        }

        $this->entityManager->flush();

        // Wyślij event
        if ($newStatus === ProgressStatus::COMPLETE) {
            $this->eventDispatcher->dispatch(
                new \App\Event\ProgressCompletedEvent($progress),
                'progress.completed'
            );
        }

        return $progress;
    }

    public function getUserProgress(int $userId, int $courseId): array
    {
        // Sprawdź czy użytkownik istnieje
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
}
