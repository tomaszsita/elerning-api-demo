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

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['complete', 'failed', 'pending'])]
    public string $action;

    public function __construct(
        int $userId,
        int $lessonId,
        string $requestId,
        string $action = 'complete'
    ) {
        $this->userId = $userId;
        $this->lessonId = $lessonId;
        $this->requestId = $requestId;
        $this->action = $action;
    }
}
