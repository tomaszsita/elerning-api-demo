<?php

namespace App\Exception;

class CourseNotFoundException extends \Exception
{
    public function __construct(int $courseId)
    {
        parent::__construct("Course {$courseId} not found");
    }
}
