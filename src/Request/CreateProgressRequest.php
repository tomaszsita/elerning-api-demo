<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateProgressRequest
{
    #[Assert\Positive]
    public int $userId;

    #[Assert\Positive]
    public int $lessonId;

    #[Assert\NotBlank]
    public string $requestId;

    public function __construct(
        int $userId,
        int $lessonId,
        string $requestId
    ) {
        $this->userId = $userId;
        $this->lessonId = $lessonId;
        $this->requestId = $requestId;
    }
}
