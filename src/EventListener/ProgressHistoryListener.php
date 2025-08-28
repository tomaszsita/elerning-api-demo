<?php

declare(strict_types = 1);

namespace App\EventListener;

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
        // Record history for new progress (oldStatus is null) or status changes
        if (null === $event->getOldStatus() || $event->hasStatusChanged()) {
            $history = $this->progressHistoryFactory->createFromEvent($event);

            $this->entityManager->persist($history);
            $this->entityManager->flush();
        }
    }
}
