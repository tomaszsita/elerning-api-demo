<?php

declare(strict_types = 1);

namespace App\Service;

use App\Entity\Course;
use App\Entity\Enrollment;
use App\Entity\User;
use App\Exception\EnrollmentException;
use App\Exception\EntityNotFoundException;
use App\Factory\EnrollmentFactory;
use App\Repository\Interfaces\CourseRepositoryInterface;
use App\Repository\Interfaces\EnrollmentRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class EnrollmentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CourseRepositoryInterface $courseRepository,
        private EnrollmentRepositoryInterface $enrollmentRepository,
        private EnrollmentFactory $enrollmentFactory
    ) {
    }

    public function enrollUser(int $userId, int $courseId): Enrollment
    {
        $user = $this->entityManager->find(User::class, $userId);
        if (!$user) {
            throw new EntityNotFoundException('User', $userId);
        }

        /** @var Course|null $course */
        $course = $this->entityManager->find(Course::class, $courseId);
        if (!$course) {
            throw new EntityNotFoundException('Course', $courseId);
        }

        if ($this->enrollmentRepository->existsByUserAndCourse($userId, $courseId)) {
            throw new EnrollmentException(EnrollmentException::ALREADY_ENROLLED, $userId, $courseId);
        }

        // Use pessimistic locking for concurrent enrollment safety
        $this->entityManager->beginTransaction();

        try {
            /** @var Course|null $course */
            $course = $this->entityManager->find(Course::class, $courseId, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);
            if (!$course) {
                throw new EntityNotFoundException('Course', $courseId);
            }

            $enrollmentCount = $this->courseRepository->countEnrollmentsByCourse($courseId);
            if ($enrollmentCount >= $course->getMaxSeats()) {
                throw new EnrollmentException(EnrollmentException::COURSE_FULL, $userId, $courseId);
            }

            $enrollment = $this->enrollmentFactory->create($user, $course);
            $this->entityManager->persist($enrollment);
            $this->entityManager->flush();
            $this->entityManager->commit();

            return $enrollment;
        } catch (\Exception $e) {
            $this->entityManager->rollback();

            throw $e;
        }
    }

    /**
     * @return Enrollment[]
     */
    public function getUserEnrollments(int $userId): array
    {
        $user = $this->entityManager->find(User::class, $userId);
        if (!$user) {
            throw new EntityNotFoundException('User', $userId);
        }

        return $this->enrollmentRepository->findByUser($userId);
    }

    /**
     * @return Course[]
     */
    public function getAllCoursesWithRemainingSeats(): array
    {
        return $this->courseRepository->findAllWithRemainingSeats();
    }
}
