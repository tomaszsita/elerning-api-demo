<?php

namespace App\Controller;

use App\Request\CreateProgressRequest;
use App\Service\ProgressService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/progress')]
class ProgressController
{
    private ProgressService $progressService;
    private ValidatorInterface $validator;

    public function __construct(
        ProgressService $progressService,
        ValidatorInterface $validator
    ) {
        $this->progressService = $progressService;
        $this->validator = $validator;
    }

    #[Route('', methods: ['POST'])]
    public function createProgress(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $createRequest = new CreateProgressRequest(
            $data['user_id'] ?? 0,
            $data['lesson_id'] ?? 0,
            $data['request_id'] ?? ''
        );

        $errors = $this->validator->validate($createRequest);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $progress = $this->progressService->createProgress(
            $createRequest->userId,
            $createRequest->lessonId,
            $createRequest->requestId
        );

        return new JsonResponse($progress->toArray(), Response::HTTP_CREATED);
    }

    #[Route('/{user_id}/courses/{course_id}', methods: ['GET'])]
    public function getUserProgress(int $user_id, int $course_id): JsonResponse
    {
        $progressList = $this->progressService->getUserProgress($user_id, $course_id);
        
        // Get course to count total lessons
        $course = $this->progressService->getCourse($course_id);
        $totalLessons = count($course->getLessons());
        $completedLessons = count(array_filter($progressList, fn($p) => $p->getStatus()->value === 'complete'));
        $percent = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        $lessonsData = [];
        foreach ($course->getLessons() as $lesson) {
            $progress = array_filter($progressList, fn($p) => $p->getLesson()->getId() === $lesson->getId());
            $status = empty($progress) ? 'pending' : reset($progress)->getStatus()->value;
            
            $lessonsData[] = [
                'id' => $lesson->getId(),
                'status' => $status
            ];
        }

        return new JsonResponse([
            'completed' => $completedLessons,
            'total' => $totalLessons,
            'percent' => $percent,
            'lessons' => $lessonsData
        ], Response::HTTP_OK);
    }

    #[Route('/{user_id}/lessons/{lesson_id}', methods: ['DELETE'])]
    public function deleteProgress(int $user_id, int $lesson_id): JsonResponse
    {
        $this->progressService->deleteProgress($user_id, $lesson_id);
        
        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
