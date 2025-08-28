<?php

declare(strict_types = 1);

namespace App\Service;

use App\Entity\Progress;
use App\Event\ProgressChangedEvent;
use App\Factory\ProgressChangedEventFactory;
use App\Repository\Interfaces\ProgressRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProgressStatusService
{
    public function __construct(
        private ProgressRepositoryInterface $progressRepository,
        private EventDispatcherInterface $eventDispatcher,
        private ProgressChangedEventFactory $progressChangedEventFactory
    ) {
    }

    public function updateProgressStatus(Progress $progress, \App\Enum\ProgressStatus $newStatus, ?string $requestId = null): void
    {
        $currentStatus = $progress->getStatus();
        $oldStatus = $currentStatus ? $currentStatus->value : null;

        // Validate status transition
        if ($currentStatus && !\App\Enum\ProgressStatus::canTransition($currentStatus, $newStatus)) {
            throw new \App\Exception\InvalidStatusTransitionException($currentStatus->value, $newStatus->value);
        }

        $progress->setStatus($newStatus);

        if (\App\Enum\ProgressStatus::COMPLETE === $newStatus) {
            $progress->setCompletedAt(new \DateTimeImmutable());
        } elseif (\App\Enum\ProgressStatus::PENDING === $newStatus) {
            $progress->setCompletedAt(null);
        }

        $this->progressRepository->save($progress);

        // Dispatch event for history tracking
        $event = $this->progressChangedEventFactory->create($progress, $oldStatus, $newStatus->value, $requestId);
        $this->eventDispatcher->dispatch($event, ProgressChangedEvent::NAME);
    }

    public function deleteProgress(int $userId, int $lessonId): void
    {
        $progress = $this->progressRepository->findByUserAndLesson($userId, $lessonId);
        if ($progress && in_array($progress->getStatus(), [\App\Enum\ProgressStatus::COMPLETE, \App\Enum\ProgressStatus::FAILED])) {
            // Reset to pending instead of deleting - preserves audit trail
            $this->updateProgressStatus($progress, \App\Enum\ProgressStatus::PENDING);
        }
    }

    public function dispatchProgressCreatedEvent(Progress $progress, string $requestId): void
    {
        // Dispatch event for new progress creation (oldStatus is null)
        $status = $progress->getStatus();
        if (!$status) {
            throw new \InvalidArgumentException('Progress status cannot be null');
        }

        $event = $this->progressChangedEventFactory->create($progress, null, $status->value, $requestId);
        $this->eventDispatcher->dispatch($event, ProgressChangedEvent::NAME);
    }
}
