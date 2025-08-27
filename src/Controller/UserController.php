<?php

namespace App\Controller;

use App\Service\CourseService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/users')]
class UserController
{
    private CourseService $courseService;

    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
    }

    #[Route('/{id}/courses', methods: ['GET'])]
    public function getUserCourses(int $id): JsonResponse
    {
        $courses = $this->courseService->getUserCourses($id);
        
        $coursesData = array_map(fn($course) => $course->toArray(), $courses);
        
        return new JsonResponse(['courses' => $coursesData], Response::HTTP_OK);
    }
}
