<?php

namespace App\Service;

use App\Entity\Progress;
use App\Event\ProgressChangedEvent;
use App\Factory\ProgressChangedEventFactory;
use App\Repository\Interfaces\ProgressRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProgressStatusService
{
    public function __construct(
        private ProgressRepositoryInterface $progressRepository,
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $eventDispatcher,
        private ProgressChangedEventFactory $progressChangedEventFactory
    ) {
    }

    public function updateProgressStatus(Progress $progress, \App\Enum\ProgressStatus $newStatus, ?string $requestId = null): void
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

    public function deleteProgress(int $userId, int $lessonId): void
    {
        $progress = $this->progressRepository->findByUserAndLesson($userId, $lessonId);
        if ($progress && in_array($progress->getStatus(), [\App\Enum\ProgressStatus::COMPLETE, \App\Enum\ProgressStatus::FAILED])) {
            // Reset to pending instead of deleting - preserves audit trail
            $this->updateProgressStatus($progress, \App\Enum\ProgressStatus::PENDING);
        }
    }
}
