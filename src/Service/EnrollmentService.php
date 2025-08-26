<?php

namespace App\Service;

use App\Entity\Enrollment;
use App\Entity\User;
use App\Entity\Course;
use App\Exception\CourseFullException;
use App\Exception\UserAlreadyEnrolledException;
use App\Exception\UserNotFoundException;
use App\Exception\CourseNotFoundException;
use App\Repository\UserRepository;
use App\Repository\CourseRepository;
use App\Repository\EnrollmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EnrollmentService
{
    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        CourseRepository $courseRepository,
        EnrollmentRepository $enrollmentRepository,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->courseRepository = $courseRepository;
        $this->enrollmentRepository = $enrollmentRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private CourseRepository $courseRepository;
    private EnrollmentRepository $enrollmentRepository;
    private EventDispatcherInterface $eventDispatcher;

    public function enrollUser(int $userId, int $courseId): Enrollment
    {
        // Sprawdź czy użytkownik istnieje
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new UserNotFoundException($userId);
        }

        // Sprawdź czy kurs istnieje
        $course = $this->courseRepository->find($courseId);
        if (!$course) {
            throw new CourseNotFoundException($courseId);
        }

        // Sprawdź czy użytkownik już jest zapisany
        if ($this->enrollmentRepository->existsByUserAndCourse($userId, $courseId)) {
            throw new UserAlreadyEnrolledException($userId, $courseId);
        }

        // Sprawdź czy są wolne miejsca (z pessimistic locking)
        $this->entityManager->beginTransaction();
        try {
            // Lock kurs dla atomic operation
            $course = $this->entityManager->find(Course::class, $courseId, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);
            
            $enrollmentCount = $this->courseRepository->countEnrollmentsByCourse($courseId);
            if ($enrollmentCount >= $course->getMaxSeats()) {
                $this->entityManager->rollback();
                throw new CourseFullException($courseId);
            }

            // Utwórz enrollment
            $enrollment = new Enrollment();
            $enrollment->setUser($user);
            $enrollment->setCourse($course);
            $enrollment->setStatus('enrolled');

            $this->entityManager->persist($enrollment);
            $this->entityManager->flush();
            $this->entityManager->commit();

            // Wyślij event
            $this->eventDispatcher->dispatch(
                new \App\Event\EnrollmentCreatedEvent($enrollment),
                'enrollment.created'
            );

            return $enrollment;
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    public function getUserEnrollments(int $userId): array
    {
        // Sprawdź czy użytkownik istnieje
        $user = $this->userRepository->find($userId);
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
