<?php

namespace App\EventListener;

use App\Entity\ProgressHistory;
use App\Event\ProgressChangedEvent;
use App\Factory\ProgressHistoryFactory;
use Doctrine\ORM\EntityManagerInterface;

class ProgressHistoryListener
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProgressHistoryFactory $progressHistoryFactory
    ) {
    }

    public function onProgressChanged(ProgressChangedEvent $event): void
    {
        if (!$event->hasStatusChanged()) {
            return; // No status change, no need to record history
        }

        $history = $this->progressHistoryFactory->createFromEvent($event);

        $this->entityManager->persist($history);
        $this->entityManager->flush();
    }
}
