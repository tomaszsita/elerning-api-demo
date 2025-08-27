<?php

namespace App\Tests\Feature;

use App\Entity\Course;
use App\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;

class CourseControllerTest extends AbstractFeature
{
    public function testListCoursesSuccess(): void
    {
        $this->client->request('GET', '/courses');

        $this->assertResponseStatusCodeSame(200);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('courses', $responseData);
        $this->assertNotEmpty($responseData['courses']);
        
        $course = $responseData['courses'][0];
        $this->assertArrayHasKey('id', $course);
        $this->assertArrayHasKey('title', $course);
        $this->assertArrayHasKey('description', $course);
        $this->assertArrayHasKey('max_seats', $course);
    }

    public function testEnrollInCourseSuccess(): void
    {
        $user = $this->getTestUser();
        $course = $this->getTestCourse();

        $this->client->request('POST', '/courses/' . $course->getId() . '/enroll', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user->getId(),
        ]));

        $this->assertResponseStatusCodeSame(201);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('user_id', $responseData);
        $this->assertArrayHasKey('course_id', $responseData);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals($user->getId(), $responseData['user_id']);
        $this->assertEquals($course->getId(), $responseData['course_id']);
        $this->assertEquals('enrolled', $responseData['status']);
    }

    #[DataProvider('enrollmentValidationProvider')]
    public function testEnrollInCourseValidationErrors(string $testCase, string $requestBody, string $expectedError): void
    {
        $course = $this->getTestCourse();

        $this->client->request('POST', '/courses/' . $course->getId() . '/enroll', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $requestBody);

        $this->assertResponseStatusCodeSame(400);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString($expectedError, $responseData['error']);
    }

    public static function enrollmentValidationProvider(): array
    {
        return [
            'invalid json' => [
                'Invalid JSON',
                'invalid json',
                'Invalid JSON'
            ],
            'missing user_id' => [
                'Missing user_id',
                json_encode([]),
                'user_id is required'
            ],
            'invalid user_id type' => [
                'Invalid user_id type',
                json_encode(['user_id' => 'invalid']),
                'user_id is required and must be numeric'
            ],
        ];
    }

    #[DataProvider('enrollmentNotFoundProvider')]
    public function testEnrollInCourseNotFound(string $entityType, int $invalidId, string $expectedError): void
    {
        if ($entityType === 'user') {
            $course = $this->getTestCourse();
            $requestData = ['user_id' => $invalidId];
        } else {
            $user = $this->getTestUser();
            $requestData = ['user_id' => $user->getId()];
        }

        $courseId = $entityType === 'user' ? $course->getId() : $invalidId;

        $this->client->request('POST', '/courses/' . $courseId . '/enroll', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($requestData));

        $this->assertResponseStatusCodeSame(404);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString($expectedError, $responseData['error']);
    }

    public static function enrollmentNotFoundProvider(): array
    {
        return [
            'user not found' => ['user', 99999, 'User 99999 not found'],
            'course not found' => ['course', 99999, 'Course 99999 not found'],
        ];
    }

    public function testEnrollInCourseAlreadyEnrolled(): void
    {
        $user = $this->getTestUser();
        $course = $this->getTestCourse();

        // First enrollment
        $this->client->request('POST', '/courses/' . $course->getId() . '/enroll', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user->getId(),
        ]));

        $this->assertResponseStatusCodeSame(201);

        // Second enrollment - should fail
        $this->client->request('POST', '/courses/' . $course->getId() . '/enroll', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user->getId(),
        ]));

        $this->assertResponseStatusCodeSame(400);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('already enrolled', $responseData['error']);
    }

    public function testEnrollInCourseFull(): void
    {
        // Create a course with only 1 seat
        $course = $this->createCourseWithLimitedSeats(1);
        
        // First user enrolls successfully
        $user1 = $this->getTestUser();
        $this->client->request('POST', '/courses/' . $course->getId() . '/enroll', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user1->getId(),
        ]));

        $this->assertResponseStatusCodeSame(201);

        // Second user tries to enroll - should fail because course is full
        $user2 = $this->createTestUser('Jane Smith', 'jane.smith@example.com');
        
        // Debug: check if user2 exists in database
        $this->entityManager->flush();
        $user2Id = $user2->getId();
        
        $this->client->request('POST', '/courses/' . $course->getId() . '/enroll', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user2Id,
        ]));

        $this->assertResponseStatusCodeSame(409); // Conflict
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('is full', $responseData['error']);
    }
}
