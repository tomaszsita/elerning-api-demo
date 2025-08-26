<?php

namespace App\Exception;

class UserNotFoundException extends \Exception
{
    public function __construct(int $userId)
    {
        parent::__construct("User {$userId} not found");
    }
}
