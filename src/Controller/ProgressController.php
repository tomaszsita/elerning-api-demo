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
    public function __construct(
        private ProgressService $progressService,
        private ValidatorInterface $validator
    ) {
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
            $data['request_id'] ?? '',
            $data['action'] ?? 'complete'
        );

        $errors = $this->validator->validate($createRequest);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $isIdempotent = $this->progressService->isIdempotentRequest($createRequest->requestId);
        
        $progress = $this->progressService->createProgress(
            $createRequest->userId,
            $createRequest->lessonId,
            $createRequest->requestId,
            $createRequest->action
        );

        return new JsonResponse($progress->toArray(), $isIdempotent ? Response::HTTP_OK : Response::HTTP_CREATED);
    }

    #[Route('/{user_id}/courses/{course_id}', methods: ['GET'])]
    public function getUserProgress(int $user_id, int $course_id): JsonResponse
    {
        $summary = $this->progressService->getUserProgressSummary($user_id, $course_id);
        
        return new JsonResponse($summary, Response::HTTP_OK);
    }

    #[Route('/{user_id}/lessons/{lesson_id}', methods: ['DELETE'])]
    public function deleteProgress(int $user_id, int $lesson_id): JsonResponse
    {
        $this->progressService->deleteProgress($user_id, $lesson_id);
        
        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    #[Route('/{user_id}/lessons/{lesson_id}/history', methods: ['GET'])]
    public function getProgressHistory(int $user_id, int $lesson_id): JsonResponse
    {
        $history = $this->progressService->getProgressHistory($user_id, $lesson_id);
        
        $historyData = array_map(fn($record) => $record->toArray(), $history);
        
        return new JsonResponse(['history' => $historyData], Response::HTTP_OK);
    }
}
