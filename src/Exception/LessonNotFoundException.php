<?php

namespace App\Exception;

class LessonNotFoundException extends \Exception
{
    public function __construct(int $lessonId)
    {
        parent::__construct("Lesson {$lessonId} not found");
    }
}
