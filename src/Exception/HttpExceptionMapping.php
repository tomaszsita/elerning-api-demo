<?php

namespace App\Exception;

class HttpExceptionMapping
{
    public const MAPPING = [
        // 400 Bad Request - Client errors
        InvalidStatusTransitionException::class => 400,

        // 404 Not Found - Resource not found
        EntityNotFoundException::class => 404,

        // 409 Conflict - Business rule violations
        PrerequisitesNotMetException::class => 409,
        EnrollmentException::class => 409,
    ];

    public static function getStatusCode(\Throwable $exception): int
    {
        $exceptionClass = get_class($exception);

        return self::MAPPING[$exceptionClass] ?? 500;
    }

    public static function getErrorMessage(\Throwable $exception): string
    {
        return $exception->getMessage();
    }
}
