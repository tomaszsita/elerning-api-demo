<?php

namespace App\Event;

use App\Entity\Progress;
use Symfony\Contracts\EventDispatcher\Event;

class ProgressCompletedEvent extends Event
{
    public function __construct(
        Progress $progress
    ) {
        $this->progress = $progress;
    }

    public Progress $progress;
}
