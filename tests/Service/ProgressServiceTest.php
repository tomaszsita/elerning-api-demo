<?php

namespace App\Tests\Service;

use App\Entity\Course;
use App\Entity\Enrollment;
use App\Entity\Lesson;
use App\Entity\Progress;
use App\Entity\User;
use App\Enum\ProgressStatus;
use App\Exception\EntityNotFoundException;
use App\Exception\EnrollmentException;
use App\Exception\PrerequisitesNotMetException;
use App\Repository\Interfaces\ProgressRepositoryInterface;
use App\Service\ProgressService;
use App\Service\ValidationService;
use App\Service\PrerequisitesService;
use App\Factory\ProgressFactory;
use App\Factory\ProgressChangedEventFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use PHPUnit\Framework\TestCase;

class ProgressServiceTest extends TestCase
{
    private ProgressService $progressService;
    private ValidationService $validationService;
    private PrerequisitesService $prerequisitesService;
    private ProgressFactory $progressFactory;
    private ProgressRepositoryInterface $progressRepository;
    private EntityManagerInterface $entityManager;
    private EventDispatcherInterface $eventDispatcher;
    private ProgressChangedEventFactory $progressChangedEventFactory;

    protected function setUp(): void
    {
        $this->validationService = $this->createMock(ValidationService::class);
        $this->prerequisitesService = $this->createMock(PrerequisitesService::class);
        $this->progressFactory = $this->createMock(ProgressFactory::class);
        $this->progressRepository = $this->createMock(ProgressRepositoryInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->progressChangedEventFactory = $this->createMock(ProgressChangedEventFactory::class);

        $this->progressService = new ProgressService(
            $this->validationService,
            $this->prerequisitesService,
            $this->progressFactory,
            $this->progressRepository,
            $this->entityManager,
            $this->eventDispatcher,
            $this->progressChangedEventFactory
        );
    }

    public function testCreateProgressSuccess(): void
    {
        $user = $this->createMock(User::class);
        $lesson = $this->createMock(Lesson::class);
        $progress = $this->createMock(Progress::class);

        $this->progressRepository->expects($this->once())
            ->method('findByRequestId')
            ->with('test-request-123')
            ->willReturn(null);

        $this->validationService->expects($this->once())
            ->method('validateAndGetUser')
            ->with(1)
            ->willReturn($user);

        $this->validationService->expects($this->once())
            ->method('validateAndGetLesson')
            ->with(1)
            ->willReturn($lesson);

        $this->validationService->expects($this->once())
            ->method('validateEnrollment')
            ->with(1, $lesson);

        $this->prerequisitesService->expects($this->once())
            ->method('checkPrerequisites')
            ->with(1, $lesson);

        $this->validationService->expects($this->once())
            ->method('validateAndGetStatus')
            ->with('complete')
            ->willReturn(\App\Enum\ProgressStatus::COMPLETE);

        $this->progressFactory->expects($this->once())
            ->method('create')
            ->with($user, $lesson, 'test-request-123', \App\Enum\ProgressStatus::COMPLETE)
            ->willReturn($progress);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($progress);

        $this->entityManager->expects($this->once())
            ->method('flush');

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

        $this->validationService->expects($this->once())
            ->method('validateAndGetUser')
            ->with(999)
            ->willThrowException(new EntityNotFoundException('User', 999));

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('User 999 not found');

        $this->progressService->createProgress(999, 1, 'test-request-123');
    }

    public function testCreateProgressLessonNotFound(): void
    {
        $user = $this->createMock(User::class);

        $this->progressRepository->expects($this->once())
            ->method('findByRequestId')
            ->willReturn(null);

        $this->validationService->expects($this->once())
            ->method('validateAndGetUser')
            ->with(1)
            ->willReturn($user);

        $this->validationService->expects($this->once())
            ->method('validateAndGetLesson')
            ->with(999)
            ->willThrowException(new EntityNotFoundException('Lesson', 999));

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Lesson 999 not found');

        $this->progressService->createProgress(1, 999, 'test-request-123');
    }

    public function testCreateProgressUserNotEnrolled(): void
    {
        $user = $this->createMock(User::class);
        $lesson = $this->createMock(Lesson::class);

        $this->progressRepository->expects($this->once())
            ->method('findByRequestId')
            ->willReturn(null);

        $this->validationService->expects($this->once())
            ->method('validateAndGetUser')
            ->with(1)
            ->willReturn($user);

        $this->validationService->expects($this->once())
            ->method('validateAndGetLesson')
            ->with(1)
            ->willReturn($lesson);

        $this->validationService->expects($this->once())
            ->method('validateEnrollment')
            ->with(1, $lesson)
            ->willThrowException(new EnrollmentException(EnrollmentException::NOT_ENROLLED, 1, 1));

        $this->expectException(EnrollmentException::class);

        $this->progressService->createProgress(1, 1, 'test-request-123');
    }

    public function testCreateProgressPrerequisitesNotMet(): void
    {
        $user = $this->createMock(User::class);
        $lesson = $this->createMock(Lesson::class);

        $this->progressRepository->expects($this->once())
            ->method('findByRequestId')
            ->willReturn(null);

        $this->validationService->expects($this->once())
            ->method('validateAndGetUser')
            ->with(1)
            ->willReturn($user);

        $this->validationService->expects($this->once())
            ->method('validateAndGetLesson')
            ->with(1)
            ->willReturn($lesson);

        $this->validationService->expects($this->once())
            ->method('validateEnrollment')
            ->with(1, $lesson);

        $this->prerequisitesService->expects($this->once())
            ->method('checkPrerequisites')
            ->with(1, $lesson)
            ->willThrowException(new PrerequisitesNotMetException(1, 1, 'Prerequisites not met'));

        $this->expectException(PrerequisitesNotMetException::class);

        $this->progressService->createProgress(1, 1, 'test-request-123');
    }



    public function testGetUserProgressSuccess(): void
    {
        $user = $this->createMock(User::class);
        $expectedProgress = [new Progress()];

        $this->validationService->expects($this->once())
            ->method('validateAndGetUser')
            ->with(1)
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
        $this->validationService->expects($this->once())
            ->method('validateAndGetUser')
            ->with(999)
            ->willThrowException(new EntityNotFoundException('User', 999));

        $this->expectException(EntityNotFoundException::class);

        $this->progressService->getUserProgress(999, 1);
    }

    public function testDeleteProgressSuccess(): void
    {
        $progress = $this->createMock(Progress::class);
        $event = $this->createMock(\App\Event\ProgressChangedEvent::class);

        $progress->expects($this->exactly(2))
            ->method('getStatus')
            ->willReturn(\App\Enum\ProgressStatus::COMPLETE);

        $this->progressRepository->expects($this->once())
            ->method('findByUserAndLesson')
            ->with(1, 1)
            ->willReturn($progress);

        $this->progressChangedEventFactory->expects($this->once())
            ->method('create')
            ->willReturn($event);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, \App\Event\ProgressChangedEvent::NAME);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->progressService->deleteProgress(1, 1);
    }

}
