<?php

namespace App\Tests\Service;

use App\Entity\Course;
use App\Entity\Enrollment;
use App\Entity\User;
use App\Exception\CourseFullException;
use App\Exception\EntityNotFoundException;
use App\Exception\UserAlreadyEnrolledException;
use App\Repository\CourseRepository;
use App\Repository\EnrollmentRepository;
use App\Repository\UserRepository;
use App\Service\EnrollmentService;
use App\Factory\EnrollmentFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
class EnrollmentServiceTest extends TestCase
{
    private EnrollmentService $enrollmentService;
    private EntityManagerInterface $entityManager;
    private CourseRepository $courseRepository;
    private EnrollmentRepository $enrollmentRepository;
    private EnrollmentFactory $enrollmentFactory;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->courseRepository = $this->createMock(CourseRepository::class);
        $this->enrollmentRepository = $this->createMock(EnrollmentRepository::class);
        $this->enrollmentFactory = $this->createMock(EnrollmentFactory::class);

        $this->enrollmentService = new EnrollmentService(
            $this->entityManager,
            $this->courseRepository,
            $this->enrollmentRepository,
            $this->enrollmentFactory
        );
    }

    public function testEnrollUserSuccess(): void
    {
        // Arrange
        $userId = 1;
        $courseId = 1;
        
        $user = $this->createMock(User::class);
        $course = $this->createMock(Course::class);
        
        $this->entityManager->expects($this->exactly(3))
            ->method('find')
            ->willReturnMap([
                [User::class, $userId, null, null, $user],
                [Course::class, $courseId, null, null, $course],
                [Course::class, $courseId, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE, null, $course]
            ]);
            
        $this->enrollmentRepository->expects($this->once())
            ->method('existsByUserAndCourse')
            ->with($userId, $courseId)
            ->willReturn(false);
            
        $this->courseRepository->expects($this->once())
            ->method('countEnrollmentsByCourse')
            ->with($courseId)
            ->willReturn(5);
            
        $course->expects($this->once())
            ->method('getMaxSeats')
            ->willReturn(20);
            
        $this->entityManager->expects($this->once())
            ->method('beginTransaction');
            
        $this->enrollmentFactory->expects($this->once())
            ->method('create')
            ->with($user, $course)
            ->willReturn($this->createMock(Enrollment::class));

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Enrollment::class));
            
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        $this->entityManager->expects($this->once())
            ->method('commit');

        // Act
        $result = $this->enrollmentService->enrollUser($userId, $courseId);

        // Assert
        $this->assertInstanceOf(Enrollment::class, $result);
    }

    public function testEnrollUserUserNotFound(): void
    {
        // Arrange
        $userId = 999;
        $courseId = 1;
        
        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(User::class, $userId)
            ->willReturn(null);

        // Act & Assert
        $this->expectException(EntityNotFoundException::class);
        $this->enrollmentService->enrollUser($userId, $courseId);
    }

    public function testEnrollUserCourseNotFound(): void
    {
        // Arrange
        $userId = 1;
        $courseId = 999;
        
        $user = $this->createMock(User::class);
        
        $this->entityManager->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap([
                [User::class, $userId, null, null, $user],
                [Course::class, $courseId, null, null, null]
            ]);

        // Act & Assert
        $this->expectException(EntityNotFoundException::class);
        $this->enrollmentService->enrollUser($userId, $courseId);
    }

    public function testEnrollUserAlreadyEnrolled(): void
    {
        // Arrange
        $userId = 1;
        $courseId = 1;
        
        $user = $this->createMock(User::class);
        $course = $this->createMock(Course::class);
        
        $this->entityManager->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap([
                [User::class, $userId, null, null, $user],
                [Course::class, $courseId, null, null, $course]
            ]);
            
        $this->enrollmentRepository->expects($this->once())
            ->method('existsByUserAndCourse')
            ->with($userId, $courseId)
            ->willReturn(true);

        // Act & Assert
        $this->expectException(UserAlreadyEnrolledException::class);
        $this->enrollmentService->enrollUser($userId, $courseId);
    }

    public function testEnrollUserCourseFull(): void
    {
        // Arrange
        $userId = 1;
        $courseId = 1;
        
        $user = $this->createMock(User::class);
        $course = $this->createMock(Course::class);
        
        $this->entityManager->expects($this->exactly(3))
            ->method('find')
            ->willReturnMap([
                [User::class, $userId, null, null, $user],
                [Course::class, $courseId, null, null, $course],
                [Course::class, $courseId, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE, null, $course]
            ]);
            
        $this->enrollmentRepository->expects($this->once())
            ->method('existsByUserAndCourse')
            ->with($userId, $courseId)
            ->willReturn(false);
            
        $this->courseRepository->expects($this->once())
            ->method('countEnrollmentsByCourse')
            ->with($courseId)
            ->willReturn(20);
            
        $course->expects($this->once())
            ->method('getMaxSeats')
            ->willReturn(20);
            
        $this->entityManager->expects($this->once())
            ->method('beginTransaction');
            
        $this->entityManager->expects($this->atLeastOnce())
            ->method('rollback');
            

            


        // Act & Assert
        $this->expectException(CourseFullException::class);
        $this->enrollmentService->enrollUser($userId, $courseId);
    }
}
