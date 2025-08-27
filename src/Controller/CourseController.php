<?php

namespace App\Controller;

use App\Service\CourseService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/courses')]
class CourseController
{
    private CourseService $courseService;

    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
    }

    #[Route('', methods: ['GET'])]
    public function listCourses(): JsonResponse
    {
        $courses = $this->courseService->getAllCoursesWithRemainingSeats();
        
        $coursesData = array_map(fn($course) => $course->toArray(), $courses);
        
        return new JsonResponse(['courses' => $coursesData], Response::HTTP_OK);
    }

    #[Route('/{id}/enroll', methods: ['POST'])]
    public function enrollInCourse(int $id, \Symfony\Component\HttpFoundation\Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $userId = $data['user_id'] ?? null;
        
        if (!$userId || !is_numeric($userId)) {
            return new JsonResponse(['error' => 'user_id is required and must be numeric'], Response::HTTP_BAD_REQUEST);
        }

        $enrollment = $this->courseService->enrollUserInCourse((int) $userId, $id);

        return new JsonResponse($enrollment->toArray(), Response::HTTP_CREATED);
    }
}
