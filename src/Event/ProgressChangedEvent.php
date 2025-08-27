<?php

namespace App\Event;

use App\Entity\Progress;
use Symfony\Contracts\EventDispatcher\Event;

class ProgressChangedEvent extends Event
{
    public const NAME = 'progress.changed';

    private Progress $progress;
    private ?string $oldStatus;
    private string $newStatus;
    private ?string $requestId;

    public function __construct(
        Progress $progress,
        ?string $oldStatus,
        string $newStatus,
        ?string $requestId = null
    ) {
        $this->progress = $progress;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->requestId = $requestId;
    }

    public function getProgress(): Progress
    {
        return $this->progress;
    }

    public function getOldStatus(): ?string
    {
        return $this->oldStatus;
    }

    public function getNewStatus(): string
    {
        return $this->newStatus;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function hasStatusChanged(): bool
    {
        return $this->oldStatus !== $this->newStatus;
    }
}
