<?php

namespace App\Factory;

use App\Entity\Enrollment;
use App\Entity\User;
use App\Entity\Course;

class EnrollmentFactory
{
    public function create(User $user, Course $course): Enrollment
    {
        $enrollment = new Enrollment();
        $enrollment->setUser($user);
        $enrollment->setCourse($course);
        $enrollment->setStatus('enrolled');
        $enrollment->setEnrolledAt(new \DateTimeImmutable());

        return $enrollment;
    }
}
