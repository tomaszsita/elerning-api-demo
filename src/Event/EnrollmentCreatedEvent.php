<?php

namespace App\Event;

use App\Entity\Enrollment;
use Symfony\Contracts\EventDispatcher\Event;

class EnrollmentCreatedEvent extends Event
{
    public function __construct(
        Enrollment $enrollment
    ) {
        $this->enrollment = $enrollment;
    }

    public Enrollment $enrollment;
}
