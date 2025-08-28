<?php

namespace App\Tests\Service;

use App\Entity\Progress;
use App\Event\ProgressChangedEvent;
use App\Factory\ProgressChangedEventFactory;
use App\Repository\Interfaces\ProgressRepositoryInterface;
use App\Service\ProgressStatusService;
use App\Enum\ProgressStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use PHPUnit\Framework\TestCase;

class ProgressStatusServiceTest extends TestCase
{
    private ProgressStatusService $progressStatusService;
    private ProgressRepositoryInterface $progressRepository;
    private EntityManagerInterface $entityManager;
    private EventDispatcherInterface $eventDispatcher;
    private ProgressChangedEventFactory $progressChangedEventFactory;

    protected function setUp(): void
    {
        $this->progressRepository = $this->createMock(ProgressRepositoryInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->progressChangedEventFactory = $this->createMock(ProgressChangedEventFactory::class);

        $this->progressStatusService = new ProgressStatusService(
            $this->progressRepository,
            $this->entityManager,
            $this->eventDispatcher,
            $this->progressChangedEventFactory
        );
    }

    public function testUpdateProgressStatusToComplete(): void
    {
        $progress = $this->createMock(Progress::class);
        $event = $this->createMock(ProgressChangedEvent::class);

        $progress->expects($this->once())
            ->method('getStatus')
            ->willReturn(ProgressStatus::PENDING);

        $progress->expects($this->once())
            ->method('setStatus')
            ->with(ProgressStatus::COMPLETE);

        $progress->expects($this->once())
            ->method('setCompletedAt')
            ->with($this->isInstanceOf(\DateTimeImmutable::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->progressChangedEventFactory->expects($this->once())
            ->method('create')
            ->with($progress, 'pending', 'complete', 'test-request-123')
            ->willReturn($event);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, ProgressChangedEvent::NAME);

        $this->progressStatusService->updateProgressStatus($progress, ProgressStatus::COMPLETE, 'test-request-123');
    }

    public function testUpdateProgressStatusToPending(): void
    {
        $progress = $this->createMock(Progress::class);
        $event = $this->createMock(ProgressChangedEvent::class);

        $progress->expects($this->once())
            ->method('getStatus')
            ->willReturn(ProgressStatus::COMPLETE);

        $progress->expects($this->once())
            ->method('setStatus')
            ->with(ProgressStatus::PENDING);

        $progress->expects($this->once())
            ->method('setCompletedAt')
            ->with(null);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->progressChangedEventFactory->expects($this->once())
            ->method('create')
            ->with($progress, 'complete', 'pending', 'test-request-123')
            ->willReturn($event);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, ProgressChangedEvent::NAME);

        $this->progressStatusService->updateProgressStatus($progress, ProgressStatus::PENDING, 'test-request-123');
    }

    public function testUpdateProgressStatusToFailed(): void
    {
        $progress = $this->createMock(Progress::class);
        $event = $this->createMock(ProgressChangedEvent::class);

        $progress->expects($this->once())
            ->method('getStatus')
            ->willReturn(ProgressStatus::PENDING);

        $progress->expects($this->once())
            ->method('setStatus')
            ->with(ProgressStatus::FAILED);

        $progress->expects($this->never())
            ->method('setCompletedAt');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->progressChangedEventFactory->expects($this->once())
            ->method('create')
            ->with($progress, 'pending', 'failed', 'test-request-123')
            ->willReturn($event);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, ProgressChangedEvent::NAME);

        $this->progressStatusService->updateProgressStatus($progress, ProgressStatus::FAILED, 'test-request-123');
    }

    public function testUpdateProgressStatusWithoutRequestId(): void
    {
        $progress = $this->createMock(Progress::class);
        $event = $this->createMock(ProgressChangedEvent::class);

        $progress->expects($this->once())
            ->method('getStatus')
            ->willReturn(ProgressStatus::PENDING);

        $progress->expects($this->once())
            ->method('setStatus')
            ->with(ProgressStatus::COMPLETE);

        $progress->expects($this->once())
            ->method('setCompletedAt')
            ->with($this->isInstanceOf(\DateTimeImmutable::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->progressChangedEventFactory->expects($this->once())
            ->method('create')
            ->with($progress, 'pending', 'complete', null)
            ->willReturn($event);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, ProgressChangedEvent::NAME);

        $this->progressStatusService->updateProgressStatus($progress, ProgressStatus::COMPLETE);
    }

    public function testDeleteProgressWithCompleteStatus(): void
    {
        $progress = $this->createMock(Progress::class);

        $this->progressRepository->expects($this->once())
            ->method('findByUserAndLesson')
            ->with(1, 1)
            ->willReturn($progress);

        $progress->expects($this->exactly(2))
            ->method('getStatus')
            ->willReturn(ProgressStatus::COMPLETE);

        $progress->expects($this->once())
            ->method('setStatus')
            ->with(ProgressStatus::PENDING);

        $progress->expects($this->once())
            ->method('setCompletedAt')
            ->with(null);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->progressStatusService->deleteProgress(1, 1);
    }

    public function testDeleteProgressWithFailedStatus(): void
    {
        $progress = $this->createMock(Progress::class);

        $this->progressRepository->expects($this->once())
            ->method('findByUserAndLesson')
            ->with(1, 1)
            ->willReturn($progress);

        $progress->expects($this->exactly(2))
            ->method('getStatus')
            ->willReturn(ProgressStatus::FAILED);

        $progress->expects($this->once())
            ->method('setStatus')
            ->with(ProgressStatus::PENDING);

        $progress->expects($this->once())
            ->method('setCompletedAt')
            ->with(null);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->progressStatusService->deleteProgress(1, 1);
    }

    public function testDeleteProgressWithPendingStatus(): void
    {
        $progress = $this->createMock(Progress::class);

        $this->progressRepository->expects($this->once())
            ->method('findByUserAndLesson')
            ->with(1, 1)
            ->willReturn($progress);

        $progress->expects($this->once())
            ->method('getStatus')
            ->willReturn(ProgressStatus::PENDING);

        // Should not update status for pending
        $progress->expects($this->never())
            ->method('setStatus');

        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->progressStatusService->deleteProgress(1, 1);
    }

    public function testDeleteProgressNotFound(): void
    {
        $this->progressRepository->expects($this->once())
            ->method('findByUserAndLesson')
            ->with(1, 1)
            ->willReturn(null);

        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->progressStatusService->deleteProgress(1, 1);
    }
}
