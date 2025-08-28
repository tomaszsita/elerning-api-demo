<?php

declare(strict_types = 1);

namespace App\Exception;

class EntityNotFoundException extends \Exception
{
    public function __construct(string $entityType, int $entityId)
    {
        parent::__construct("{$entityType} {$entityId} not found");
    }
}
