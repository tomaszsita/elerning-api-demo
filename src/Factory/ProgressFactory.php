<?php

declare(strict_types = 1);

namespace App\Factory;

use App\Entity\Lesson;
use App\Entity\Progress;
use App\Entity\User;
use App\Enum\ProgressStatus;

class ProgressFactory
{
    public function create(
        User $user,
        Lesson $lesson,
        string $requestId,
        ProgressStatus $status
    ): Progress {
        $progress = new Progress();
        $progress->setUser($user);
        $progress->setLesson($lesson);
        $progress->setRequestId($requestId);
        $progress->setStatus($status);

        if (ProgressStatus::COMPLETE === $status) {
            $progress->setCompletedAt(new \DateTimeImmutable());
        }

        return $progress;
    }
}
