<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateEnrollmentRequest
{
    public function __construct(
        #[Assert\Positive]
        int $userId,

        #[Assert\Positive]
        int $courseId
    ) {
        $this->userId = $userId;
        $this->courseId = $courseId;
    }

    public int $userId;
    public int $courseId;
}
