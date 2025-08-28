<?php

declare(strict_types = 1);

namespace App\Tests\Service;

use App\Entity\Lesson;
use App\Entity\Progress;
use App\Entity\User;
use App\Enum\ProgressStatus;
use App\Exception\EnrollmentException;
use App\Exception\EntityNotFoundException;
use App\Exception\PrerequisitesNotMetException;
use App\Exception\ProgressException;
use App\Factory\ProgressFactory;
use App\Repository\Interfaces\ProgressRepositoryInterface;
use App\Service\PrerequisitesService;
use App\Service\ProgressCreationService;
use App\Service\ProgressStatusService;
use App\Service\ValidationService;
use PHPUnit\Framework\TestCase;

class ProgressCreationServiceTest extends TestCase
{
    private ProgressCreationService $progressCreationService;

    private ValidationService $validationService;

    private PrerequisitesService $prerequisitesService;

    private ProgressStatusService $progressStatusService;

    private ProgressFactory $progressFactory;

    private ProgressRepositoryInterface $progressRepository;

    protected function setUp(): void
    {
        $this->validationService = $this->createMock(ValidationService::class);
        $this->prerequisitesService = $this->createMock(PrerequisitesService::class);
        $this->progressStatusService = $this->createMock(ProgressStatusService::class);
        $this->progressFactory = $this->createMock(ProgressFactory::class);
        $this->progressRepository = $this->createMock(ProgressRepositoryInterface::class);

        $this->progressCreationService = new ProgressCreationService(
            $this->validationService,
            $this->prerequisitesService,
            $this->progressStatusService,
            $this->progressFactory,
            $this->progressRepository
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

        $this->progressRepository->expects($this->once())
            ->method('findByUserAndLesson')
            ->with(1, 1)
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
            ->willReturn(ProgressStatus::COMPLETE);

        $this->progressFactory->expects($this->once())
            ->method('create')
            ->with($user, $lesson, 'test-request-123', ProgressStatus::COMPLETE)
            ->willReturn($progress);

        $this->progressRepository->expects($this->once())
            ->method('save')
            ->with($progress);

        $this->progressStatusService->expects($this->once())
            ->method('dispatchProgressCreatedEvent')
            ->with($progress, 'test-request-123');

        $result = $this->progressCreationService->createProgress(1, 1, 'test-request-123', 'complete');

        $this->assertSame($progress, $result);
    }

    public function testCreateProgressIdempotency(): void
    {
        $existingProgress = $this->createMock(Progress::class);
        $user = $this->createMock(User::class);
        $lesson = $this->createMock(Lesson::class);

        $existingProgress->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $existingProgress->expects($this->once())
            ->method('getLesson')
            ->willReturn($lesson);

        $user->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $lesson->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->progressRepository->expects($this->once())
            ->method('findByRequestId')
            ->with('test-request-123')
            ->willReturn($existingProgress);

        $result = $this->progressCreationService->createProgress(1, 1, 'test-request-123', 'complete');

        $this->assertSame($existingProgress, $result);
    }

    public function testCreateProgressRequestIdConflict(): void
    {
        $existingProgress = $this->createMock(Progress::class);
        $user = $this->createMock(User::class);
        $lesson = $this->createMock(Lesson::class);

        $existingProgress->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $existingProgress->expects($this->once())
            ->method('getLesson')
            ->willReturn($lesson);

        $user->expects($this->once())
            ->method('getId')
            ->willReturn(2); // Different user

        $lesson->expects($this->once())
            ->method('getId')
            ->willReturn(1); // Same lesson

        $this->progressRepository->expects($this->once())
            ->method('findByRequestId')
            ->with('test-request-123')
            ->willReturn($existingProgress);

        $this->expectException(ProgressException::class);
        $this->expectExceptionMessage("Request ID 'test-request-123' already exists with different user/lesson combination (user: 1, lesson: 1)");

        $this->progressCreationService->createProgress(1, 1, 'test-request-123', 'complete');
    }

    public function testCreateProgressWithFailedAction(): void
    {
        $existingProgress = $this->createMock(Progress::class);
        $newStatus = ProgressStatus::FAILED;

        $existingProgress->expects($this->once())
            ->method('getStatus')
            ->willReturn(ProgressStatus::COMPLETE);

        $this->progressRepository->expects($this->once())
            ->method('findByRequestId')
            ->with('test-request-123')
            ->willReturn(null);

        $this->progressRepository->expects($this->once())
            ->method('findByUserAndLesson')
            ->with(1, 1)
            ->willReturn($existingProgress);

        $this->validationService->expects($this->once())
            ->method('validateAndGetStatus')
            ->with('failed')
            ->willReturn($newStatus);

        $this->progressStatusService->expects($this->once())
            ->method('updateProgressStatus')
            ->with($existingProgress, $newStatus, 'test-request-123');

        $result = $this->progressCreationService->createProgress(1, 1, 'test-request-123', 'failed');

        $this->assertSame($existingProgress, $result);
    }

    public function testCreateProgressWithPendingAction(): void
    {
        $existingProgress = $this->createMock(Progress::class);
        $newStatus = ProgressStatus::PENDING;

        $existingProgress->expects($this->once())
            ->method('getStatus')
            ->willReturn(ProgressStatus::COMPLETE);

        $this->progressRepository->expects($this->once())
            ->method('findByRequestId')
            ->with('test-request-123')
            ->willReturn(null);

        $this->progressRepository->expects($this->once())
            ->method('findByUserAndLesson')
            ->with(1, 1)
            ->willReturn($existingProgress);

        $this->validationService->expects($this->once())
            ->method('validateAndGetStatus')
            ->with('pending')
            ->willReturn($newStatus);

        $this->progressStatusService->expects($this->once())
            ->method('updateProgressStatus')
            ->with($existingProgress, $newStatus, 'test-request-123');

        $result = $this->progressCreationService->createProgress(1, 1, 'test-request-123', 'pending');

        $this->assertSame($existingProgress, $result);
    }

    public function testCreateProgressUserNotFound(): void
    {
        $this->progressRepository->expects($this->once())
            ->method('findByRequestId')
            ->with('test-request-123')
            ->willReturn(null);

        $this->progressRepository->expects($this->once())
            ->method('findByUserAndLesson')
            ->with(1, 1)
            ->willReturn(null);

        $this->validationService->expects($this->once())
            ->method('validateAndGetUser')
            ->with(1)
            ->willThrowException(new EntityNotFoundException('User', 1));

        $this->expectException(EntityNotFoundException::class);

        $this->progressCreationService->createProgress(1, 1, 'test-request-123', 'complete');
    }

    public function testCreateProgressLessonNotFound(): void
    {
        $user = $this->createMock(User::class);

        $this->progressRepository->expects($this->once())
            ->method('findByRequestId')
            ->with('test-request-123')
            ->willReturn(null);

        $this->progressRepository->expects($this->once())
            ->method('findByUserAndLesson')
            ->with(1, 1)
            ->willReturn(null);

        $this->validationService->expects($this->once())
            ->method('validateAndGetUser')
            ->with(1)
            ->willReturn($user);

        $this->validationService->expects($this->once())
            ->method('validateAndGetLesson')
            ->with(1)
            ->willThrowException(new EntityNotFoundException('Lesson', 1));

        $this->expectException(EntityNotFoundException::class);

        $this->progressCreationService->createProgress(1, 1, 'test-request-123', 'complete');
    }

    public function testCreateProgressUserNotEnrolled(): void
    {
        $user = $this->createMock(User::class);
        $lesson = $this->createMock(Lesson::class);

        $this->progressRepository->expects($this->once())
            ->method('findByRequestId')
            ->with('test-request-123')
            ->willReturn(null);

        $this->progressRepository->expects($this->once())
            ->method('findByUserAndLesson')
            ->with(1, 1)
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

        $this->progressCreationService->createProgress(1, 1, 'test-request-123', 'complete');
    }

    public function testCreateProgressPrerequisitesNotMet(): void
    {
        $user = $this->createMock(User::class);
        $lesson = $this->createMock(Lesson::class);

        $this->progressRepository->expects($this->once())
            ->method('findByRequestId')
            ->with('test-request-123')
            ->willReturn(null);

        $this->progressRepository->expects($this->once())
            ->method('findByUserAndLesson')
            ->with(1, 1)
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

        $this->progressCreationService->createProgress(1, 1, 'test-request-123', 'complete');
    }

    public function testIsIdempotentRequestByRequestId(): void
    {
        $this->progressRepository->expects($this->once())
            ->method('findByRequestId')
            ->with('test-request-123')
            ->willReturn($this->createMock(Progress::class));

        $result = $this->progressCreationService->isIdempotentRequest('test-request-123', 1, 1, 'complete');

        $this->assertTrue($result);
    }

    public function testIsIdempotentRequestBySameStatus(): void
    {
        $existingProgress = $this->createMock(Progress::class);

        $this->progressRepository->expects($this->once())
            ->method('findByRequestId')
            ->with('test-request-123')
            ->willReturn(null);

        $this->progressRepository->expects($this->once())
            ->method('findByUserAndLesson')
            ->with(1, 1)
            ->willReturn($existingProgress);

        $existingProgress->expects($this->once())
            ->method('getStatus')
            ->willReturn(ProgressStatus::COMPLETE);

        $this->validationService->expects($this->once())
            ->method('validateAndGetStatus')
            ->with('complete')
            ->willReturn(ProgressStatus::COMPLETE);

        $result = $this->progressCreationService->isIdempotentRequest('test-request-123', 1, 1, 'complete');

        $this->assertTrue($result);
    }

    public function testIsIdempotentRequestReturnsFalseForNewProgress(): void
    {
        $this->progressRepository->expects($this->once())
            ->method('findByRequestId')
            ->with('test-request-123')
            ->willReturn(null);

        $this->progressRepository->expects($this->once())
            ->method('findByUserAndLesson')
            ->with(1, 1)
            ->willReturn(null);

        $result = $this->progressCreationService->isIdempotentRequest('test-request-123', 1, 1, 'complete');

        $this->assertFalse($result);
    }

    public function testIsIdempotentRequestReturnsFalseForStatusChange(): void
    {
        $existingProgress = $this->createMock(Progress::class);

        $this->progressRepository->expects($this->once())
            ->method('findByRequestId')
            ->with('test-request-123')
            ->willReturn(null);

        $this->progressRepository->expects($this->once())
            ->method('findByUserAndLesson')
            ->with(1, 1)
            ->willReturn($existingProgress);

        $existingProgress->expects($this->once())
            ->method('getStatus')
            ->willReturn(ProgressStatus::PENDING);

        $this->validationService->expects($this->once())
            ->method('validateAndGetStatus')
            ->with('complete')
            ->willReturn(ProgressStatus::COMPLETE);

        $result = $this->progressCreationService->isIdempotentRequest('test-request-123', 1, 1, 'complete');

        $this->assertFalse($result);
    }
}
