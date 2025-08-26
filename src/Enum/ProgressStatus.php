<?php

namespace App\Enum;

class ProgressStatus
{
    public const PENDING = 'pending';
    public const COMPLETE = 'complete';
    public const FAILED = 'failed';

    public static function canTransition(string $fromStatus, string $toStatus): bool
    {
        if ($fromStatus === self::PENDING) {
            return in_array($toStatus, [self::COMPLETE, self::FAILED]);
        }
        
        if ($fromStatus === self::FAILED) {
            return $toStatus === self::COMPLETE;
        }
        
        if ($fromStatus === self::COMPLETE) {
            return false; // Nie można zmienić z complete
        }
        
        return false;
    }

    public static function getAllowedTransitions(string $status): array
    {
        if ($status === self::PENDING) {
            return [self::COMPLETE, self::FAILED];
        }
        
        if ($status === self::FAILED) {
            return [self::COMPLETE];
        }
        
        if ($status === self::COMPLETE) {
            return [];
        }
        
        return [];
    }

    public static function isFinal(string $status): bool
    {
        return $status === self::COMPLETE;
    }

    public static function isValid(string $status): bool
    {
        return in_array($status, [self::PENDING, self::COMPLETE, self::FAILED]);
    }
}
