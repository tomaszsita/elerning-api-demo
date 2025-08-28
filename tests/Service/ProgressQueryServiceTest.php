<?php

declare(strict_types = 1);

namespace App\Tests\Service;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\Progress;
use App\Entity\ProgressHistory;
use App\Entity\User;
use App\Exception\EntityNotFoundException;
use App\Repository\Interfaces\ProgressHistoryRepositoryInterface;
use App\Repository\Interfaces\ProgressRepositoryInterface;
use App\Service\ProgressQueryService;
use App\Service\ValidationService;
use PHPUnit\Framework\TestCase;

class ProgressQueryServiceTest extends TestCase
{
    private ProgressQueryService $progressQueryService;

    private ValidationService $validationService;

    private ProgressRepositoryInterface $progressRepository;

    private ProgressHistoryRepositoryInterface $progressHistoryRepository;

    protected function setUp(): void
    {
        $this->validationService = $this->createMock(ValidationService::class);
        $this->progressRepository = $this->createMock(ProgressRepositoryInterface::class);
        $this->progressHistoryRepository = $this->createMock(ProgressHistoryRepositoryInterface::class);

        $this->progressQueryService = new ProgressQueryService(
            $this->validationService,
            $this->progressRepository,
            $this->progressHistoryRepository
        );
    }

    public function testGetUserProgressSuccess(): void
    {
        $user = $this->createMock(User::class);
        $progressList = [$this->createMock(Progress::class)];

        $this->validationService->expects($this->once())
            ->method('validateAndGetUser')
            ->with(1)
            ->willReturn($user);

        $this->progressRepository->expects($this->once())
            ->method('findByUserAndCourse')
            ->with(1, 1)
            ->willReturn($progressList);

        $result = $this->progressQueryService->getUserProgress(1, 1);

        $this->assertSame($progressList, $result);
    }

    public function testGetUserProgressUserNotFound(): void
    {
        $this->validationService->expects($this->once())
            ->method('validateAndGetUser')
            ->with(1)
            ->willThrowException(new EntityNotFoundException('User', 1));

        $this->expectException(EntityNotFoundException::class);

        $this->progressQueryService->getUserProgress(1, 1);
    }

    public function testGetProgressHistorySuccess(): void
    {
        $user = $this->createMock(User::class);
        $lesson = $this->createMock(Lesson::class);
        $historyList = [$this->createMock(ProgressHistory::class)];

        $this->validationService->expects($this->once())
            ->method('validateAndGetUser')
            ->with(1)
            ->willReturn($user);

        $this->validationService->expects($this->once())
            ->method('validateAndGetLesson')
            ->with(1)
            ->willReturn($lesson);

        $this->progressHistoryRepository->expects($this->once())
            ->method('findByUserAndLesson')
            ->with(1, 1)
            ->willReturn($historyList);

        $result = $this->progressQueryService->getProgressHistory(1, 1);

        $this->assertSame($historyList, $result);
    }

    public function testGetProgressHistoryUserNotFound(): void
    {
        $this->validationService->expects($this->once())
            ->method('validateAndGetUser')
            ->with(1)
            ->willThrowException(new EntityNotFoundException('User', 1));

        $this->expectException(EntityNotFoundException::class);

        $this->progressQueryService->getProgressHistory(1, 1);
    }

    public function testGetProgressHistoryLessonNotFound(): void
    {
        $user = $this->createMock(User::class);

        $this->validationService->expects($this->once())
            ->method('validateAndGetUser')
            ->with(1)
            ->willReturn($user);

        $this->validationService->expects($this->once())
            ->method('validateAndGetLesson')
            ->with(1)
            ->willThrowException(new EntityNotFoundException('Lesson', 1));

        $this->expectException(EntityNotFoundException::class);

        $this->progressQueryService->getProgressHistory(1, 1);
    }

    public function testGetUserProgressSummarySimple(): void
    {
        $user = $this->createMock(User::class);
        $course = $this->createMock(Course::class);
        $lesson = $this->createMock(Lesson::class);
        $progress = $this->createMock(Progress::class);

        $this->validationService->expects($this->exactly(2))
            ->method('validateAndGetUser')
            ->with(1)
            ->willReturn($user);

        $this->validationService->expects($this->once())
            ->method('validateAndGetCourse')
            ->with(1)
            ->willReturn($course);

        $this->progressRepository->expects($this->once())
            ->method('findByUserAndCourse')
            ->with(1, 1)
            ->willReturn([$progress]);

        $course->expects($this->exactly(2))
            ->method('getLessons')
            ->willReturn(new \Doctrine\Common\Collections\ArrayCollection([$lesson]));

        $lesson->expects($this->exactly(3))
            ->method('getId')
            ->willReturn(1);

        $progress->expects($this->exactly(1))
            ->method('getLesson')
            ->willReturn($lesson);

        $progress->expects($this->exactly(2))
            ->method('getStatus')
            ->willReturn(\App\Enum\ProgressStatus::COMPLETE);

        $result = $this->progressQueryService->getUserProgressSummary(1, 1);

        $this->assertArrayHasKey('completed', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('percent', $result);
        $this->assertArrayHasKey('lessons', $result);
        $this->assertEquals(1, $result['completed']);
        $this->assertEquals(1, $result['total']);
        $this->assertEquals(100, $result['percent']);
        $this->assertCount(1, $result['lessons']);
    }

    public function testGetUserProgressSummaryUserNotFound(): void
    {
        $this->validationService->expects($this->once())
            ->method('validateAndGetUser')
            ->with(1)
            ->willThrowException(new EntityNotFoundException('User', 1));

        $this->expectException(EntityNotFoundException::class);

        $this->progressQueryService->getUserProgressSummary(1, 1);
    }

    public function testGetUserProgressSummaryCourseNotFound(): void
    {
        $user = $this->createMock(User::class);

        $this->validationService->expects($this->once())
            ->method('validateAndGetUser')
            ->with(1)
            ->willReturn($user);

        $this->validationService->expects($this->once())
            ->method('validateAndGetCourse')
            ->with(1)
            ->willThrowException(new EntityNotFoundException('Course', 1));

        $this->expectException(EntityNotFoundException::class);

        $this->progressQueryService->getUserProgressSummary(1, 1);
    }

    public function testGetUserProgressSummaryWithNoLessons(): void
    {
        $user = $this->createMock(User::class);
        $course = $this->createMock(Course::class);

        $this->validationService->expects($this->exactly(2))
            ->method('validateAndGetUser')
            ->with(1)
            ->willReturn($user);

        $this->validationService->expects($this->once())
            ->method('validateAndGetCourse')
            ->with(1)
            ->willReturn($course);

        $this->progressRepository->expects($this->once())
            ->method('findByUserAndCourse')
            ->with(1, 1)
            ->willReturn([]);

        $course->expects($this->exactly(2))
            ->method('getLessons')
            ->willReturn(new \Doctrine\Common\Collections\ArrayCollection([]));

        $result = $this->progressQueryService->getUserProgressSummary(1, 1);

        $this->assertEquals(0, $result['completed']);
        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['percent']);
        $this->assertEmpty($result['lessons']);
    }
}
