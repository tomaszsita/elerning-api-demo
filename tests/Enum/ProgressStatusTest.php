<?php

namespace App\Tests\Enum;

use App\Enum\ProgressStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProgressStatusTest extends TestCase
{
    #[DataProvider('validTransitionsProvider')]
    public function testValidTransitions(ProgressStatus $fromStatus, ProgressStatus $toStatus): void
    {
        $this->assertTrue(ProgressStatus::canTransition($fromStatus, $toStatus));
    }

    public static function validTransitionsProvider(): array
    {
        return [
            'PENDING to COMPLETE' => [ProgressStatus::PENDING, ProgressStatus::COMPLETE],
            'PENDING to FAILED' => [ProgressStatus::PENDING, ProgressStatus::FAILED],
            'FAILED to COMPLETE' => [ProgressStatus::FAILED, ProgressStatus::COMPLETE],
            'FAILED to PENDING' => [ProgressStatus::FAILED, ProgressStatus::PENDING],
            'COMPLETE to PENDING' => [ProgressStatus::COMPLETE, ProgressStatus::PENDING],
        ];
    }

    #[DataProvider('invalidTransitionsProvider')]
    public function testInvalidTransitions(ProgressStatus $fromStatus, ProgressStatus $toStatus): void
    {
        $this->assertFalse(ProgressStatus::canTransition($fromStatus, $toStatus));
    }

    public static function invalidTransitionsProvider(): array
    {
        return [
            'COMPLETE to FAILED' => [ProgressStatus::COMPLETE, ProgressStatus::FAILED],
            'PENDING to PENDING' => [ProgressStatus::PENDING, ProgressStatus::PENDING],
        ];
    }

    #[DataProvider('getAllowedTransitionsProvider')]
    public function testGetAllowedTransitions(ProgressStatus $status, array $expectedTransitions): void
    {
        $this->assertEquals($expectedTransitions, ProgressStatus::getAllowedTransitions($status));
    }

    public static function getAllowedTransitionsProvider(): array
    {
        return [
            'PENDING allowed transitions' => [
                ProgressStatus::PENDING,
                [ProgressStatus::COMPLETE, ProgressStatus::FAILED]
            ],
            'FAILED allowed transitions' => [
                ProgressStatus::FAILED,
                [ProgressStatus::COMPLETE, ProgressStatus::PENDING]
            ],
            'COMPLETE allowed transitions' => [
                ProgressStatus::COMPLETE,
                [ProgressStatus::PENDING]
            ],
        ];
    }

    #[DataProvider('isFinalProvider')]
    public function testIsFinal(ProgressStatus $status, bool $expected): void
    {
        $this->assertEquals($expected, ProgressStatus::isFinal($status));
    }

    public static function isFinalProvider(): array
    {
        return [
            'COMPLETE is final' => [ProgressStatus::COMPLETE, true],
            'PENDING is not final' => [ProgressStatus::PENDING, false],
            'FAILED is not final' => [ProgressStatus::FAILED, false],
        ];
    }

    #[DataProvider('fromStringProvider')]
    public function testFromString(string $input, ProgressStatus $expected): void
    {
        $this->assertEquals($expected, ProgressStatus::fromString($input));
    }

    public static function fromStringProvider(): array
    {
        return [
            'pending string' => ['pending', ProgressStatus::PENDING],
            'complete string' => ['complete', ProgressStatus::COMPLETE],
            'failed string' => ['failed', ProgressStatus::FAILED],
        ];
    }

    public function testFromStringInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ProgressStatus::fromString('invalid_status');
    }

    // Test data provider functionality
    public function testDataProviderExample(): void
    {
        $testCases = [
            ['pending', ProgressStatus::PENDING],
            ['complete', ProgressStatus::COMPLETE],
            ['failed', ProgressStatus::FAILED],
        ];

        foreach ($testCases as [$input, $expected]) {
            $this->assertEquals($expected, ProgressStatus::fromString($input));
        }
    }
}
