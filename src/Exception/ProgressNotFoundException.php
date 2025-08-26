<?php

namespace App\Exception;

class ProgressNotFoundException extends \Exception
{
    public function __construct(int $progressId)
    {
        parent::__construct("Progress {$progressId} not found");
    }
}
