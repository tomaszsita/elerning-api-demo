<?php

declare(strict_types = 1);

namespace App\Exception;

class PrerequisitesNotMetException extends \Exception
{
    public function __construct(int $userId, int $lessonId, string $message = '')
    {
        $defaultMessage = "User {$userId} has not completed prerequisites for lesson {$lessonId}";
        parent::__construct($message ?: $defaultMessage);
    }
}
