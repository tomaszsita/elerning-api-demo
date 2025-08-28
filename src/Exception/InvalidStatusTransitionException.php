<?php

declare(strict_types = 1);

namespace App\Exception;

class InvalidStatusTransitionException extends \Exception
{
    public function __construct(string $fromStatus, string $toStatus)
    {
        parent::__construct(
            sprintf('Invalid status transition from "%s" to "%s"', $fromStatus, $toStatus)
        );
    }
}
