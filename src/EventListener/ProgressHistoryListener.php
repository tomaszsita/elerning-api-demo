<?php

namespace App\EventListener;

use App\Entity\ProgressHistory;
use App\Event\ProgressChangedEvent;
use App\Factory\ProgressHistoryFactory;
use Doctrine\ORM\EntityManagerInterface;

class ProgressHistoryListener
{
    private EntityManagerInterface $entityManager;
    private ProgressHistoryFactory $progressHistoryFactory;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProgressHistoryFactory $progressHistoryFactory
    ) {
        $this->entityManager = $entityManager;
        $this->progressHistoryFactory = $progressHistoryFactory;
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
