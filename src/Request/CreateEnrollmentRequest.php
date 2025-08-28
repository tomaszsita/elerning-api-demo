<?php

declare(strict_types = 1);

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateEnrollmentRequest
{
    #[Assert\Positive]
    public int $userId;

    #[Assert\Positive]
    public int $courseId;

    public function __construct(
        int $userId,
        int $courseId
    ) {
        $this->userId = $userId;
        $this->courseId = $courseId;
    }
}
