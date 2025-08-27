<?php

namespace App\Tests\Enum;

use App\Enum\ProgressStatus;
use PHPUnit\Framework\TestCase;

class ProgressStatusTest extends TestCase
{
    public function testValidTransitions(): void
    {
        $validTransitions = [
            [ProgressStatus::PENDING, ProgressStatus::COMPLETE],
            [ProgressStatus::PENDING, ProgressStatus::FAILED],
            [ProgressStatus::FAILED, ProgressStatus::COMPLETE],
            [ProgressStatus::COMPLETE, ProgressStatus::PENDING], // Can reset to pending
        ];

        foreach ($validTransitions as [$fromStatus, $toStatus]) {
            $this->assertTrue(ProgressStatus::canTransition($fromStatus, $toStatus));
        }
    }

    public function testInvalidTransitions(): void
    {
        $invalidTransitions = [
            [ProgressStatus::COMPLETE, ProgressStatus::FAILED], // Cannot go from complete to failed
            [ProgressStatus::FAILED, ProgressStatus::PENDING], // Cannot go from failed to pending
            [ProgressStatus::PENDING, ProgressStatus::PENDING], // Cannot stay in same status
        ];

        foreach ($invalidTransitions as [$fromStatus, $toStatus]) {
            $this->assertFalse(ProgressStatus::canTransition($fromStatus, $toStatus));
        }
    }

    public function testGetAllowedTransitions(): void
    {
        $this->assertEquals(
            [ProgressStatus::COMPLETE, ProgressStatus::FAILED],
            ProgressStatus::getAllowedTransitions(ProgressStatus::PENDING)
        );

        $this->assertEquals(
            [ProgressStatus::COMPLETE],
            ProgressStatus::getAllowedTransitions(ProgressStatus::FAILED)
        );

        $this->assertEquals(
            [ProgressStatus::PENDING], // Can reset to pending
            ProgressStatus::getAllowedTransitions(ProgressStatus::COMPLETE)
        );
    }

    public function testIsFinal(): void
    {
        $this->assertTrue(ProgressStatus::isFinal(ProgressStatus::COMPLETE));
        $this->assertFalse(ProgressStatus::isFinal(ProgressStatus::PENDING));
        $this->assertFalse(ProgressStatus::isFinal(ProgressStatus::FAILED));
    }

    public function testFromString(): void
    {
        $this->assertEquals(ProgressStatus::PENDING, ProgressStatus::fromString('pending'));
        $this->assertEquals(ProgressStatus::COMPLETE, ProgressStatus::fromString('complete'));
        $this->assertEquals(ProgressStatus::FAILED, ProgressStatus::fromString('failed'));
    }

    public function testFromStringInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ProgressStatus::fromString('invalid_status');
    }


}
