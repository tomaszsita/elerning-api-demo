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

        try {
            $progress = $this->progressService->createProgress(
                $createRequest->userId,
                $createRequest->lessonId,
                $createRequest->requestId
            );

            return new JsonResponse([
                'id' => $progress->getId(),
                'user_id' => $progress->getUser()->getId(),
                'lesson_id' => $progress->getLesson()->getId(),
                'lesson_title' => $progress->getLesson()->getTitle(),
                'status' => $progress->getStatus(),
                'request_id' => $progress->getRequestId(),
                'completed_at' => $progress->getCompletedAt() ? $progress->getCompletedAt()->format('Y-m-d H:i:s') : null
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            // Exception mapping będzie obsługiwane przez ExceptionListener
            throw $e;
        }
    }

    #[Route('', methods: ['GET'])]
    public function getUserProgress(Request $request): JsonResponse
    {
        $userId = $request->query->get('user_id');
        $courseId = $request->query->get('course_id');
        
        if (!$userId || !is_numeric($userId)) {
            return new JsonResponse(['error' => 'user_id parameter is required and must be numeric'], Response::HTTP_BAD_REQUEST);
        }

        if (!$courseId || !is_numeric($courseId)) {
            return new JsonResponse(['error' => 'course_id parameter is required and must be numeric'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $progressList = $this->progressService->getUserProgress((int) $userId, (int) $courseId);

            $progressData = array_map(function ($progress) {
                return [
                    'id' => $progress->getId(),
                    'user_id' => $progress->getUser()->getId(),
                    'lesson_id' => $progress->getLesson()->getId(),
                    'lesson_title' => $progress->getLesson()->getTitle(),
                    'status' => $progress->getStatus(),
                    'request_id' => $progress->getRequestId(),
                    'completed_at' => $progress->getCompletedAt() ? $progress->getCompletedAt()->format('Y-m-d H:i:s') : null
                ];
            }, $progressList);

            return new JsonResponse(['progress' => $progressData], Response::HTTP_OK);

        } catch (\Exception $e) {
            // Exception mapping będzie obsługiwane przez ExceptionListener
            throw $e;
        }
    }
}
