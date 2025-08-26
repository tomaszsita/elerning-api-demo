<?php

namespace App\Exception;

class UserNotEnrolledException extends \Exception
{
    public function __construct(int $userId, int $courseId)
    {
        parent::__construct("User {$userId} is not enrolled in course {$courseId}");
    }
}
