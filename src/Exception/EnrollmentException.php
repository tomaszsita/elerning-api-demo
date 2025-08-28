<?php

declare(strict_types = 1);

namespace App\Exception;

class EnrollmentException extends \Exception
{
    public const ALREADY_ENROLLED = 'already_enrolled';

    public const NOT_ENROLLED = 'not_enrolled';

    public const COURSE_FULL = 'course_full';

    private string $type;

    public function __construct(string $type, int $userId, int $courseId)
    {
        $this->type = $type;

        switch ($type) {
            case self::ALREADY_ENROLLED:
                $message = "User {$userId} is already enrolled in course {$courseId}";

                break;
            case self::NOT_ENROLLED:
                $message = "User {$userId} is not enrolled in course {$courseId}";

                break;
            case self::COURSE_FULL:
                $message = "Course {$courseId} is full and cannot accept more enrollments";

                break;
            default:
                $message = 'Enrollment error occurred';
        }

        parent::__construct($message);
    }

    public function getType(): string
    {
        return $this->type;
    }
}
