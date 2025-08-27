<?php

namespace App\EventListener;

use App\Entity\ProgressHistory;
use App\Event\ProgressChangedEvent;
use Doctrine\ORM\EntityManagerInterface;

class ProgressHistoryListener
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public function onProgressChanged(ProgressChangedEvent $event): void
    {
        if (!$event->hasStatusChanged()) {
            return; // No status change, no need to record history
        }

        $progress = $event->getProgress();
        
        $history = new ProgressHistory();
        $history->setProgress($progress);
        $history->setUser($progress->getUser());
        $history->setLesson($progress->getLesson());
        $history->setOldStatus($event->getOldStatus());
        $history->setNewStatus($event->getNewStatus());
        $history->setRequestId($event->getRequestId());

        $this->entityManager->persist($history);
        $this->entityManager->flush();
    }
}
