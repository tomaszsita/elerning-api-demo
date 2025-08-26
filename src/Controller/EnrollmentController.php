<?php

namespace App\Controller;

use App\Request\CreateEnrollmentRequest;
use App\Service\EnrollmentService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/enrollments')]
class EnrollmentController
{
    private EnrollmentService $enrollmentService;
    private ValidatorInterface $validator;

    public function __construct(
        EnrollmentService $enrollmentService,
        ValidatorInterface $validator
    ) {
        $this->enrollmentService = $enrollmentService;
        $this->validator = $validator;
    }

    #[Route('', methods: ['POST'])]
    public function createEnrollment(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $createRequest = new CreateEnrollmentRequest(
            $data['user_id'] ?? 0,
            $data['course_id'] ?? 0
        );

        $errors = $this->validator->validate($createRequest);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $enrollment = $this->enrollmentService->enrollUser(
            $createRequest->userId,
            $createRequest->courseId
        );

        return new JsonResponse([
            'id' => $enrollment->getId(),
            'user_id' => $enrollment->getUser()->getId(),
            'course_id' => $enrollment->getCourse()->getId(),
            'status' => $enrollment->getStatus(),
            'enrolled_at' => $enrollment->getEnrolledAt()->format('Y-m-d H:i:s'),
            'completed_at' => $enrollment->getCompletedAt() ? $enrollment->getCompletedAt()->format('Y-m-d H:i:s') : null
        ], Response::HTTP_CREATED);
    }

    #[Route('', methods: ['GET'])]
    public function getUserEnrollments(Request $request): JsonResponse
    {
        $userId = $request->query->get('user_id');
        
        if (!$userId || !is_numeric($userId)) {
            return new JsonResponse(['error' => 'user_id parameter is required and must be numeric'], Response::HTTP_BAD_REQUEST);
        }

        $enrollments = $this->enrollmentService->getUserEnrollments((int) $userId);

        $enrollmentsData = array_map(function ($enrollment) {
            return [
                'id' => $enrollment->getId(),
                'user_id' => $enrollment->getUser()->getId(),
                'course_id' => $enrollment->getCourse()->getId(),
                'course_title' => $enrollment->getCourse()->getTitle(),
                'status' => $enrollment->getStatus(),
                'enrolled_at' => $enrollment->getEnrolledAt()->format('Y-m-d H:i:s'),
                'completed_at' => $enrollment->getCompletedAt() ? $enrollment->getCompletedAt()->format('Y-m-d H:i:s') : null
            ];
        }, $enrollments);

        return new JsonResponse(['enrollments' => $enrollmentsData], Response::HTTP_OK);
    }
}
