<?php

namespace App\Tests\Exception;

use App\Exception\HttpExceptionMapping;
use App\Exception\InvalidStatusTransitionException;
use App\Exception\EntityNotFoundException;
use App\Exception\EnrollmentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HttpExceptionMappingTest extends TestCase
{
    #[DataProvider('statusCodeProvider')]
    public function testGetStatusCode(\Exception $exception, int $expectedStatusCode): void
    {
        $this->assertEquals($expectedStatusCode, HttpExceptionMapping::getStatusCode($exception));
    }

    public static function statusCodeProvider(): array
    {
        return [
            'InvalidStatusTransitionException returns 400' => [
                new InvalidStatusTransitionException('pending', 'invalid'),
                400
            ],
            'EntityNotFoundException returns 404' => [
                new EntityNotFoundException('User', 123),
                404
            ],
            'EnrollmentException returns 409' => [
                new EnrollmentException(EnrollmentException::COURSE_FULL, 1, 456),
                409
            ],
            'Unknown exception returns 500' => [
                new \Exception('Unknown error'),
                500
            ],
        ];
    }

    public function testGetErrorMessage(): void
    {
        $exception = new EntityNotFoundException('User', 123);
        $expectedMessage = 'User 123 not found';
        
        $this->assertEquals($expectedMessage, HttpExceptionMapping::getErrorMessage($exception));
    }
}
