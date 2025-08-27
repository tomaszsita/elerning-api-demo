<?php

namespace App\Service;

use App\Entity\Progress;
use App\Factory\ProgressFactory;
use App\Repository\Interfaces\ProgressRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
class ProgressService
{
    public function __construct(
        ValidationService $validationService,
        PrerequisitesService $prerequisitesService,
        ProgressFactory $progressFactory,
        ProgressRepositoryInterface $progressRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->validationService = $validationService;
        $this->prerequisitesService = $prerequisitesService;
        $this->progressFactory = $progressFactory;
        $this->progressRepository = $progressRepository;
        $this->entityManager = $entityManager;
    }

    private ValidationService $validationService;
    private PrerequisitesService $prerequisitesService;
    private ProgressFactory $progressFactory;
    private ProgressRepositoryInterface $progressRepository;
    private EntityManagerInterface $entityManager;

    public function createProgress(int $userId, int $lessonId, string $requestId, string $status = 'complete'): Progress
    {
        $existingProgress = $this->progressRepository->findByRequestId($requestId);
        if ($existingProgress) {
            return $existingProgress;
        }

        $user = $this->validationService->validateAndGetUser($userId);
        $lesson = $this->validationService->validateAndGetLesson($lessonId);
        $this->validationService->validateEnrollment($userId, $lesson);
        $this->prerequisitesService->checkPrerequisites($userId, $lesson);
        $progressStatus = $this->validationService->validateAndGetStatus($status);

        $progress = $this->progressFactory->create($user, $lesson, $requestId, $progressStatus);
        $this->saveProgress($progress);

        return $progress;
    }

    /**
     * @return Progress[]
     */
    public function getUserProgress(int $userId, int $courseId): array
    {
        $this->validationService->validateAndGetUser($userId);
        return $this->progressRepository->findByUserAndCourse($userId, $courseId);
    }

    private function saveProgress(Progress $progress): void
    {
        $this->entityManager->persist($progress);
        $this->entityManager->flush();
    }

    public function getCourse(int $courseId): \App\Entity\Course
    {
        $course = $this->entityManager->find(\App\Entity\Course::class, $courseId);
        if (!$course) {
            throw new \App\Exception\CourseNotFoundException($courseId);
        }
        return $course;
    }

    public function deleteProgress(int $userId, int $lessonId): void
    {
        $progress = $this->progressRepository->findByUserAndLesson($userId, $lessonId);
        if ($progress) {
            $this->entityManager->remove($progress);
            $this->entityManager->flush();
        }
    }
}
