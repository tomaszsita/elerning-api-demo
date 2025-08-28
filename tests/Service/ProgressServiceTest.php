<?php

namespace App\Tests\Service;

use App\Entity\Progress;
use App\Service\ProgressService;
use App\Service\ProgressCreationService;
use App\Service\ProgressStatusService;
use App\Service\ProgressQueryService;
use PHPUnit\Framework\TestCase;

class ProgressServiceTest extends TestCase
{
    private ProgressService $progressService;
    private ProgressCreationService $progressCreationService;
    private ProgressStatusService $progressStatusService;
    private ProgressQueryService $progressQueryService;

    protected function setUp(): void
    {
        $this->progressCreationService = $this->createMock(ProgressCreationService::class);
        $this->progressStatusService = $this->createMock(ProgressStatusService::class);
        $this->progressQueryService = $this->createMock(ProgressQueryService::class);

        $this->progressService = new ProgressService(
            $this->progressCreationService,
            $this->progressStatusService,
            $this->progressQueryService
        );
    }

    public function testCreateProgress(): void
    {
        $progress = $this->createMock(Progress::class);

        $this->progressCreationService->expects($this->once())
            ->method('createProgress')
            ->with(1, 1, 'test-request-123', 'complete')
            ->willReturn($progress);

        $result = $this->progressService->createProgress(1, 1, 'test-request-123', 'complete');

        $this->assertSame($progress, $result);
    }

    public function testIsIdempotentRequest(): void
    {
        $this->progressCreationService->expects($this->once())
            ->method('isIdempotentRequest')
            ->with('test-request-123')
            ->willReturn(true);

        $result = $this->progressService->isIdempotentRequest('test-request-123');

        $this->assertTrue($result);
    }

    public function testGetUserProgress(): void
    {
        $progressList = [$this->createMock(Progress::class)];

        $this->progressQueryService->expects($this->once())
            ->method('getUserProgress')
            ->with(1, 1)
            ->willReturn($progressList);

        $result = $this->progressService->getUserProgress(1, 1);

        $this->assertSame($progressList, $result);
    }

    public function testGetProgressHistory(): void
    {
        $historyList = [$this->createMock(\App\Entity\ProgressHistory::class)];

        $this->progressQueryService->expects($this->once())
            ->method('getProgressHistory')
            ->with(1, 1)
            ->willReturn($historyList);

        $result = $this->progressService->getProgressHistory(1, 1);

        $this->assertSame($historyList, $result);
    }

    public function testGetUserProgressSummary(): void
    {
        $summary = ['completed' => 1, 'total' => 2, 'percent' => 50, 'lessons' => []];

        $this->progressQueryService->expects($this->once())
            ->method('getUserProgressSummary')
            ->with(1, 1)
            ->willReturn($summary);

        $result = $this->progressService->getUserProgressSummary(1, 1);

        $this->assertSame($summary, $result);
    }

    public function testDeleteProgress(): void
    {
        $this->progressStatusService->expects($this->once())
            ->method('deleteProgress')
            ->with(1, 1);

        $this->progressService->deleteProgress(1, 1);
    }
}
