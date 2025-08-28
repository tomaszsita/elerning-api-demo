<?php

declare(strict_types = 1);

namespace App\Factory;

use App\Entity\Progress;
use App\Event\ProgressChangedEvent;

class ProgressChangedEventFactory
{
    public function create(Progress $progress, ?string $oldStatus, string $newStatus, ?string $requestId = null): ProgressChangedEvent
    {
        return new ProgressChangedEvent($progress, $oldStatus, $newStatus, $requestId);
    }
}
