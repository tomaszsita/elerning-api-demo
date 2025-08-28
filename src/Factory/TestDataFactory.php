<?php

declare(strict_types = 1);

namespace App\Factory;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\User;

class TestDataFactory
{
    public function createUser(string $name, string $email): User
    {
        $user = new User();
        $user->setName($name);
        $user->setEmail($email);

        return $user;
    }

    public function createCourse(string $title, string $description, int $maxSeats): Course
    {
        $course = new Course();
        $course->setTitle($title);
        $course->setDescription($description);
        $course->setMaxSeats($maxSeats);

        return $course;
    }

    public function createLesson(string $title, string $content, int $orderIndex, Course $course): Lesson
    {
        $lesson = new Lesson();
        $lesson->setTitle($title);
        $lesson->setContent($content);
        $lesson->setOrderIndex($orderIndex);
        $lesson->setCourse($course);

        return $lesson;
    }
}
