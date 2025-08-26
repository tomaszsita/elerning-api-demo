<?php

namespace App\Tests\Service;

use App\Entity\Course;
use App\Entity\Enrollment;
use App\Entity\Lesson;
use App\Entity\Progress;
use App\Entity\User;
use App\Enum\ProgressStatus;
use App\Event\ProgressCompletedEvent;
use App\Exception\LessonNotFoundException;
use App\Exception\PrerequisitesNotMetException;
use App\Exception\UserNotEnrolledException;
use App\Exception\UserNotFoundException;
use App\Repository\Interfaces\EnrollmentRepositoryInterface;
use App\Repository\Interfaces\LessonRepositoryInterface;
use App\Repository\Interfaces\ProgressRepositoryInterface;
use App\Repository\Interfaces\UserRepositoryInterface;
use App\Service\ProgressService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProgressServiceTest extends TestCase
{
    private ProgressService $progressService;
    private EntityManagerInterface $entityManager;
    private UserRepositoryInterface $userRepository;
    private LessonRepositoryInterface $lessonRepository;
    private ProgressRepositoryInterface $progressRepository;
    private EnrollmentRepositoryInterface $enrollmentRepository;
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->lessonRepository = $this->createMock(LessonRepositoryInterface::class);
        $this->progressRepository = $this->createMock(ProgressRepositoryInterface::class);
        $this->enrollmentRepository = $this->createMock(EnrollmentRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->progressService = new ProgressService(
            $this->entityManager,
            $this->userRepository,
            $this->lessonRepository,
            $this->progressRepository,
            $this->enrollmentRepository,
            $this->eventDispatcher
        );
    }

    public function testCreateProgressSuccess(): void
    {
        $user = $this->createMock(User::class);
        $lesson = $this->createMock(Lesson::class);
        $course = $this->createMock(Course::class);
        $progress = $this->createMock(Progress::class);

        $this->progressRepository->expects($this->once())
            ->method('findByRequestId')
            ->with('test-request-123')
            ->willReturn(null);

        $this->entityManager->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap([
                [User::class, 1, $user],
                [Lesson::class, 1, $lesson]
            ]);

        $lesson->expects($this->exactly(2))
            ->method('getCourse')
            ->willReturn($course);

        $course->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(1);

        $this->enrollmentRepository->expects($this->once())
            ->method('existsByUserAndCourse')
            ->with(1, 1)
            ->willReturn(true);

        $lesson->expects($this->once())
            ->method('getOrderIndex')
            ->willReturn(1);

        $this->lessonRepository->expects($this->once())
            ->method('findByCourseAndOrderLessThan')
            ->with(1, 1)
            ->willReturn([]);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Progress::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(ProgressCompletedEvent::class), 'progress.completed');

        $result = $this->progressService->createProgress(1, 1, 'test-request-123', 'complete');

        $this->assertInstanceOf(Progress::class, $result);
    }

    public function testCreateProgressIdempotency(): void
    {
        $existingProgress = $this->createMock(Progress::class);

        $this->progressRepository->expects($this->once())
            ->method('findByRequestId')
            ->with('test-request-123')
            ->willReturn($existingProgress);

        $result = $this->progressService->createProgress(1, 1, 'test-request-123', 'complete');

        $this->assertSame($existingProgress, $result);
    }

    public function testCreateProgressUserNotFound(): void
    {
        $this->progressRepository->expects($this->once())
            ->method('findByRequestId')
            ->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(User::class, 999)
            ->willReturn(null);

        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage('User 999 not found');

        $this->progressService->createProgress(999, 1, 'test-request-123');
    }

    public function testCreateProgressLessonNotFound(): void
    {
        $user = $this->createMock(User::class);

        $this->progressRepository->expects($this->once())
            ->method('findByRequestId')
            ->willReturn(null);

        $this->entityManager->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap([
                [User::class, 1, $user],
                [Lesson::class, 999, null]
            ]);

        $this->expectException(LessonNotFoundException::class);
        $this->expectExceptionMessage('Lesson 999 not found');

        $this->progressService->createProgress(1, 999, 'test-request-123');
    }

    public function testCreateProgressUserNotEnrolled(): void
    {
        $user = $this->createMock(User::class);
        $lesson = $this->createMock(Lesson::class);
        $course = $this->createMock(Course::class);

        $this->progressRepository->expects($this->once())
            ->method('findByRequestId')
            ->willReturn(null);

        $this->entityManager->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap([
                [User::class, 1, $user],
                [Lesson::class, 1, $lesson]
            ]);

        $lesson->expects($this->exactly(2))
            ->method('getCourse')
            ->willReturn($course);

        $course->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(1);

        $this->enrollmentRepository->expects($this->once())
            ->method('existsByUserAndCourse')
            ->with(1, 1)
            ->willReturn(false);

        $this->expectException(UserNotEnrolledException::class);

        $this->progressService->createProgress(1, 1, 'test-request-123');
    }

    public function testCreateProgressPrerequisitesNotMet(): void
    {
        $user = $this->createMock(User::class);
        $lesson = $this->createMock(Lesson::class);
        $course = $this->createMock(Course::class);
        $prerequisiteLesson = $this->createMock(Lesson::class);

        $this->progressRepository->expects($this->once())
            ->method('findByRequestId')
            ->willReturn(null);

        $this->entityManager->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap([
                [User::class, 1, $user],
                [Lesson::class, 1, $lesson]
            ]);

        $lesson->expects($this->exactly(2))
            ->method('getCourse')
            ->willReturn($course);

        $course->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(1);

        $this->enrollmentRepository->expects($this->once())
            ->method('existsByUserAndCourse')
            ->with(1, 1)
            ->willReturn(true);

        $lesson->expects($this->once())
            ->method('getOrderIndex')
            ->willReturn(2);

        $this->lessonRepository->expects($this->once())
            ->method('findByCourseAndOrderLessThan')
            ->with(1, 2)
            ->willReturn([$prerequisiteLesson]);

        $prerequisiteLesson->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $prerequisiteLesson->expects($this->once())
            ->method('getTitle')
            ->willReturn('Prerequisite Lesson');

        $lesson->expects($this->once())
            ->method('getTitle')
            ->willReturn('Current Lesson');

        $lesson->expects($this->once())
            ->method('getId')
            ->willReturn(2);

        $this->progressRepository->expects($this->once())
            ->method('findByUserAndLesson')
            ->with(1, 1)
            ->willReturn(null);

        $this->expectException(PrerequisitesNotMetException::class);

        $this->progressService->createProgress(1, 1, 'test-request-123');
    }



    public function testGetUserProgressSuccess(): void
    {
        $user = $this->createMock(User::class);
        $expectedProgress = [new Progress()];

        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(User::class, 1)
            ->willReturn($user);

        $this->progressRepository->expects($this->once())
            ->method('findByUserAndCourse')
            ->with(1, 1)
            ->willReturn($expectedProgress);

        $result = $this->progressService->getUserProgress(1, 1);

        $this->assertSame($expectedProgress, $result);
    }

    public function testGetUserProgressUserNotFound(): void
    {
        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(User::class, 999)
            ->willReturn(null);

        $this->expectException(UserNotFoundException::class);

        $this->progressService->getUserProgress(999, 1);
    }


}
