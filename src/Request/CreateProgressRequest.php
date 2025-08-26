<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateProgressRequest
{
    public function __construct(
        #[Assert\Positive]
        int $userId,

        #[Assert\Positive]
        int $lessonId,

        #[Assert\NotBlank]
        string $requestId
    ) {
        $this->userId = $userId;
        $this->lessonId = $lessonId;
        $this->requestId = $requestId;
    }

    public int $userId;
    public int $lessonId;
    public string $requestId;
}
