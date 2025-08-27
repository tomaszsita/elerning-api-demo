<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Enrollment;
use App\Exception\CourseNotFoundException;
use App\Exception\UserNotFoundException;
use App\Factory\EnrollmentFactory;
use App\Repository\Interfaces\CourseRepositoryInterface;
use App\Repository\Interfaces\EnrollmentRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class CourseService
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

    /**
     * @return Course[]
     */
    public function getAllCoursesWithRemainingSeats(): array
    {
        return $this->courseRepository->findAllWithRemainingSeats();
    }

    public function enrollUserInCourse(int $userId, int $courseId): Enrollment
    {
        $user = $this->entityManager->find(\App\Entity\User::class, $userId);
        if (!$user) {
            throw new UserNotFoundException($userId);
        }

        $course = $this->entityManager->find(Course::class, $courseId);
        if (!$course) {
            throw new CourseNotFoundException($courseId);
        }

        if ($this->enrollmentRepository->existsByUserAndCourse($userId, $courseId)) {
            throw new \App\Exception\UserAlreadyEnrolledException($userId, $courseId);
        }

        $this->entityManager->beginTransaction();
        try {
            $course = $this->entityManager->find(Course::class, $courseId, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);
            
            $enrollmentCount = $this->courseRepository->countEnrollmentsByCourse($courseId);
            if ($enrollmentCount >= $course->getMaxSeats()) {
                $this->entityManager->rollback();
                throw new \App\Exception\CourseFullException($courseId);
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
     * @return Course[]
     */
    public function getUserCourses(int $userId): array
    {
        $user = $this->entityManager->find(\App\Entity\User::class, $userId);
        if (!$user) {
            throw new UserNotFoundException($userId);
        }

        return $this->courseRepository->findByUser($userId);
    }
}
