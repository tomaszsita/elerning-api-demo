<?php

namespace App\Service;

use App\Entity\Enrollment;
use App\Entity\User;
use App\Entity\Course;
use App\Exception\CourseFullException;
use App\Exception\UserAlreadyEnrolledException;
use App\Exception\UserNotFoundException;
use App\Exception\CourseNotFoundException;
use App\Repository\Interfaces\CourseRepositoryInterface;
use App\Repository\Interfaces\EnrollmentRepositoryInterface;
use App\Factory\EnrollmentFactory;
use Doctrine\ORM\EntityManagerInterface;
class EnrollmentService
{
    public function __construct(
        EntityManagerInterface $entityManager,
        CourseRepositoryInterface $courseRepository,
        EnrollmentRepositoryInterface $enrollmentRepository,
        EnrollmentFactory $enrollmentFactory
    ) {
        $this->entityManager = $entityManager;
        $this->courseRepository = $courseRepository;
        $this->enrollmentRepository = $enrollmentRepository;
        $this->enrollmentFactory = $enrollmentFactory;
    }

    private EntityManagerInterface $entityManager;
    private CourseRepositoryInterface $courseRepository;
    private EnrollmentRepositoryInterface $enrollmentRepository;
    private EnrollmentFactory $enrollmentFactory;

    public function enrollUser(int $userId, int $courseId): Enrollment
    {
        $user = $this->entityManager->find(User::class, $userId);
        if (!$user) {
            throw new UserNotFoundException($userId);
        }

        $course = $this->entityManager->find(Course::class, $courseId);
        if (!$course) {
            throw new CourseNotFoundException($courseId);
        }

        if ($this->enrollmentRepository->existsByUserAndCourse($userId, $courseId)) {
            throw new UserAlreadyEnrolledException($userId, $courseId);
        }

        $this->entityManager->beginTransaction();
        try {
            $course = $this->entityManager->find(Course::class, $courseId, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);
            
            $enrollmentCount = $this->courseRepository->countEnrollmentsByCourse($courseId);
            if ($enrollmentCount >= $course->getMaxSeats()) {
                $this->entityManager->rollback();
                throw new CourseFullException($courseId);
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
            throw new UserNotFoundException($userId);
        }

        return $this->enrollmentRepository->findByUser($userId);
    }

    public function isUserEnrolled(int $userId, int $courseId): bool
    {
        return $this->enrollmentRepository->existsByUserAndCourse($userId, $courseId);
    }
}
