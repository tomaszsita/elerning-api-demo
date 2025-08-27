<?php

namespace App\Factory;

use App\Entity\Progress;
use App\Entity\User;
use App\Entity\Lesson;
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

        if ($status === ProgressStatus::COMPLETE) {
            $progress->setCompletedAt(new \DateTimeImmutable());
        }

        return $progress;
    }
}
