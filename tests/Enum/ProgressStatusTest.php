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
        ];

        foreach ($validTransitions as [$fromStatus, $toStatus]) {
            $this->assertTrue(ProgressStatus::canTransition($fromStatus, $toStatus));
        }
    }

    public function testInvalidTransitions(): void
    {
        $invalidTransitions = [
            [ProgressStatus::COMPLETE, ProgressStatus::PENDING],
            [ProgressStatus::COMPLETE, ProgressStatus::FAILED],
            [ProgressStatus::FAILED, ProgressStatus::PENDING],
            [ProgressStatus::PENDING, ProgressStatus::PENDING],
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
            [],
            ProgressStatus::getAllowedTransitions(ProgressStatus::COMPLETE)
        );
    }

    public function testIsFinal(): void
    {
        $this->assertTrue(ProgressStatus::isFinal(ProgressStatus::COMPLETE));
        $this->assertFalse(ProgressStatus::isFinal(ProgressStatus::PENDING));
        $this->assertFalse(ProgressStatus::isFinal(ProgressStatus::FAILED));
    }

    public function testIsValid(): void
    {
        $this->assertTrue(ProgressStatus::isValid(ProgressStatus::PENDING));
        $this->assertTrue(ProgressStatus::isValid(ProgressStatus::COMPLETE));
        $this->assertTrue(ProgressStatus::isValid(ProgressStatus::FAILED));
        $this->assertFalse(ProgressStatus::isValid('invalid_status'));
    }


}
