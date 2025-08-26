<?php

namespace App\Exception;

class CourseFullException extends \Exception
{
    public function __construct(int $courseId)
    {
        parent::__construct("Course {$courseId} is full and cannot accept more enrollments");
    }
}
