<?php

namespace App\Factory;

use App\Entity\Progress;
use App\Entity\ProgressHistory;
use App\Event\ProgressChangedEvent;

class ProgressHistoryFactory
{
    public function createFromEvent(ProgressChangedEvent $event): ProgressHistory
    {
        $progress = $event->getProgress();
        
        $history = new ProgressHistory();
        $history->setProgress($progress);
        $history->setUser($progress->getUser());
        $history->setLesson($progress->getLesson());
        $history->setOldStatus($event->getOldStatus());
        $history->setNewStatus($event->getNewStatus());
        $history->setRequestId($event->getRequestId());
        
        return $history;
    }
}
