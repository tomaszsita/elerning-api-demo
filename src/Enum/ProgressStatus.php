<?php

namespace App\Enum;

enum ProgressStatus: string
{
    case PENDING = 'pending';
    case COMPLETE = 'complete';
    case FAILED = 'failed';

    public static function canTransition(self $fromStatus, self $toStatus): bool
    {
        return match ($fromStatus) {
            self::PENDING => in_array($toStatus, [self::COMPLETE, self::FAILED]),
            self::FAILED => $toStatus === self::COMPLETE,
            self::COMPLETE => false, // Cannot change from complete
        };
    }

    /**
     * @return array<int, ProgressStatus>
     */
    public static function getAllowedTransitions(self $status): array
    {
        return match ($status) {
            self::PENDING => [self::COMPLETE, self::FAILED],
            self::FAILED => [self::COMPLETE],
            self::COMPLETE => [],
        };
    }

    public static function isFinal(self $status): bool
    {
        return $status === self::COMPLETE;
    }

    public static function fromString(string $value): self
    {
        return match ($value) {
            'pending' => self::PENDING,
            'complete' => self::COMPLETE,
            'failed' => self::FAILED,
            default => throw new \InvalidArgumentException("Invalid status: {$value}"),
        };
    }
}
