<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\User;
use App\Entity\Lesson;
use App\Enum\ProgressStatus;
use App\Exception\EntityNotFoundException;
use App\Exception\InvalidStatusTransitionException;
use App\Repository\Interfaces\EnrollmentRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class ValidationService
{
    public function __construct(
        EntityManagerInterface $entityManager,
        EnrollmentRepositoryInterface $enrollmentRepository
    ) {
        $this->entityManager = $entityManager;
        $this->enrollmentRepository = $enrollmentRepository;
    }

    private EntityManagerInterface $entityManager;
    private EnrollmentRepositoryInterface $enrollmentRepository;

    public function validateAndGetUser(int $userId): User
    {
        $user = $this->entityManager->find(User::class, $userId);
        if (!$user) {
            throw new EntityNotFoundException('User', $userId);
        }
        return $user;
    }

    public function validateAndGetLesson(int $lessonId): Lesson
    {
        $lesson = $this->entityManager->find(Lesson::class, $lessonId);
        if (!$lesson) {
            throw new EntityNotFoundException('Lesson', $lessonId);
        }
        return $lesson;
    }

    public function validateAndGetCourse(int $courseId): Course
    {
        $course = $this->entityManager->find(Course::class, $courseId);
        if (!$course) {
            throw new EntityNotFoundException('Course', $courseId);
        }
        return $course;
    }

    public function validateEnrollment(int $userId, Lesson $lesson): void
    {
        if (!$this->enrollmentRepository->existsByUserAndCourse($userId, $lesson->getCourse()->getId())) {
            throw new \App\Exception\UserNotEnrolledException($userId, $lesson->getCourse()->getId());
        }
    }

    public function validateAndGetStatus(string $status): ProgressStatus
    {
        try {
            return ProgressStatus::fromString($status);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidStatusTransitionException('', $status);
        }
    }
}
