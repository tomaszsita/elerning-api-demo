<?php

namespace App\Controller;

use App\Service\EnrollmentService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/users')]
class UserController
{
    private EnrollmentService $enrollmentService;

    public function __construct(EnrollmentService $enrollmentService)
    {
        $this->enrollmentService = $enrollmentService;
    }

    #[Route('/{id}/courses', methods: ['GET'])]
    public function getUserCourses(int $id): JsonResponse
    {
        $enrollments = $this->enrollmentService->getUserEnrollments($id);
        $courses = array_map(fn($enrollment) => $enrollment->getCourse(), $enrollments);
        
        $coursesData = array_map(fn($course) => $course->toArray(), $courses);
        
        return new JsonResponse(['courses' => $coursesData], Response::HTTP_OK);
    }
}
