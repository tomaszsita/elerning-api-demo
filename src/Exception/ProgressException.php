<?php

declare(strict_types = 1);

namespace App\Exception;

class ProgressException extends \Exception
{
    public const REQUEST_ID_CONFLICT = 'request_id_conflict';

    private string $type;

    public function __construct(string $type, string $requestId, int $userId, int $lessonId)
    {
        $this->type = $type;

        switch ($type) {
            case self::REQUEST_ID_CONFLICT:
                $message = "Request ID '{$requestId}' already exists with different user/lesson combination (user: {$userId}, lesson: {$lessonId})";

                break;
            default:
                $message = 'Progress error occurred';
        }

        parent::__construct($message);
    }

    public function getType(): string
    {
        return $this->type;
    }
}
