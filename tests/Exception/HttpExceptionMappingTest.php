<?php

namespace App\Tests\Exception;

use App\Exception\HttpExceptionMapping;
use App\Exception\InvalidStatusTransitionException;
use App\Exception\UserNotFoundException;
use App\Exception\CourseFullException;
use PHPUnit\Framework\TestCase;

class HttpExceptionMappingTest extends TestCase
{
    public function testGetStatusCode(): void
    {
        $this->assertEquals(400, HttpExceptionMapping::getStatusCode(
            new InvalidStatusTransitionException('pending', 'invalid')
        ));

        $this->assertEquals(404, HttpExceptionMapping::getStatusCode(
            new UserNotFoundException(123)
        ));

        $this->assertEquals(409, HttpExceptionMapping::getStatusCode(
            new CourseFullException(456)
        ));
    }

    public function testGetErrorMessage(): void
    {
        $exception = new UserNotFoundException(123);
        $expectedMessage = 'User 123 not found';
        
        $this->assertEquals($expectedMessage, HttpExceptionMapping::getErrorMessage($exception));
    }

    public function testUnknownExceptionReturns500(): void
    {
        $exception = new \Exception('Unknown error');
        
        $this->assertEquals(500, HttpExceptionMapping::getStatusCode($exception));
    }


}
