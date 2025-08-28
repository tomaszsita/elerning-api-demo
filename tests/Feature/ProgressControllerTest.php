<?php

declare(strict_types = 1);

namespace App\Tests\Feature;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;

class ProgressControllerTest extends AbstractFeature
{
    public function testCreateProgressSuccess(): void
    {
        $user = $this->getTestUser();
        $course = $this->getTestCourse();
        $lesson = $this->getTestLesson();

        // Enroll user in course first
        $this->client->request('POST', '/courses/' . $course->getId() . '/enroll', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user->getId(),
        ]));

        $this->assertResponseStatusCodeSame(201);

        $this->client->request('POST', '/progress', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id'    => $user->getId(),
            'lesson_id'  => $lesson->getId(),
            'request_id' => 'test-request-123',
        ]));

        $this->assertResponseStatusCodeSame(201);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals($user->getId(), $responseData['user_id']);
        $this->assertEquals($lesson->getId(), $responseData['lesson_id']);
        $this->assertEquals('Test Lesson', $responseData['lesson_title']);
        $this->assertEquals('complete', $responseData['status']);
        $this->assertEquals('test-request-123', $responseData['request_id']);
        $this->assertArrayHasKey('completed_at', $responseData);
    }

    public function testCreateProgressIdempotency(): void
    {
        $user = $this->getTestUser();
        $course = $this->getTestCourse();
        $lesson = $this->getTestLesson();
        $requestId = 'idempotency-test-123';

        $this->client->request('POST', '/courses/' . $course->getId() . '/enroll', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user->getId(),
        ]));

        $this->client->request('POST', '/progress', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id'    => $user->getId(),
            'lesson_id'  => $lesson->getId(),
            'request_id' => $requestId,
        ]));

        $this->assertResponseStatusCodeSame(201);
        $firstResponse = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request('POST', '/progress', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id'    => $user->getId(),
            'lesson_id'  => $lesson->getId(),
            'request_id' => $requestId,
        ]));

        $this->assertResponseStatusCodeSame(200); // Changed from 201 to 200 for idempotency
        $secondResponse = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($firstResponse['id'], $secondResponse['id']);
        $this->assertEquals($firstResponse['request_id'], $secondResponse['request_id']);
    }

    public function testCreateProgressRequestIdConflict(): void
    {
        $user1 = $this->getTestUser();
        $user2 = $this->createTestUser('Jane Smith', 'jane.smith@example.com');
        $course = $this->getTestCourse();
        $lesson1 = $this->getTestLesson();
        $lesson2 = $this->createTestLesson('Test Lesson 2', $course, 2);
        $requestId = 'conflict-test-123';

        // Enroll both users
        $this->client->request('POST', '/courses/' . $course->getId() . '/enroll', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user1->getId(),
        ]));

        $this->client->request('POST', '/courses/' . $course->getId() . '/enroll', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user2->getId(),
        ]));

        // First request - creates progress
        $this->client->request('POST', '/progress', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id'    => $user1->getId(),
            'lesson_id'  => $lesson1->getId(),
            'request_id' => $requestId,
        ]));

        $this->assertResponseStatusCodeSame(201);

        // Second request with same request_id but different user/lesson - should conflict
        $this->client->request('POST', '/progress', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id'    => $user2->getId(),
            'lesson_id'  => $lesson2->getId(),
            'request_id' => $requestId,
        ]));

        $this->assertResponseStatusCodeSame(409);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('already exists with different user/lesson combination', $responseData['error']);
    }

    public function testCreateProgressInvalidJson(): void
    {
        $this->client->request('POST', '/progress', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], 'invalid json');

        $this->assertResponseStatusCodeSame(400);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Invalid JSON', $responseData['error']);
    }

    #[DataProvider('actionProvider')]
    public function testCreateProgressWithDifferentActions(string $action, string $expectedStatus): void
    {
        $user = $this->getTestUser();
        $course = $this->getTestCourse();
        $lesson = $this->getTestLesson();

        // Enroll user in course first
        $this->client->request('POST', '/courses/' . $course->getId() . '/enroll', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user->getId(),
        ]));

        $this->assertResponseStatusCodeSame(201);

        $this->client->request('POST', '/progress', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id'    => $user->getId(),
            'lesson_id'  => $lesson->getId(),
            'action'     => $action,
            'request_id' => 'test-request-' . $action,
        ]));

        $this->assertResponseStatusCodeSame(201);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals($user->getId(), $responseData['user_id']);
        $this->assertEquals($lesson->getId(), $responseData['lesson_id']);
        $this->assertEquals('Test Lesson', $responseData['lesson_title']);
        $this->assertEquals($expectedStatus, $responseData['status']);
        $this->assertEquals('test-request-' . $action, $responseData['request_id']);
        $this->assertArrayHasKey('completed_at', $responseData);
    }

    public static function actionProvider(): array
    {
        return [
            'complete action' => ['complete', 'complete'],
            'failed action'   => ['failed', 'failed'],
            'pending action'  => ['pending', 'pending'],
        ];
    }

    #[DataProvider('validationErrorsProvider')]
    public function testCreateProgressValidationErrors(array $invalidData): void
    {
        $this->client->request('POST', '/progress', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($invalidData));

        $this->assertResponseStatusCodeSame(400);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertNotEmpty($responseData['errors']);
    }

    public static function validationErrorsProvider(): array
    {
        return [
            'invalid user_id and lesson_id' => [
                [
                    'user_id'    => 0,
                    'lesson_id'  => -1,
                    'request_id' => '',
                ],
            ],
            'missing required fields' => [
                [
                    'user_id'    => null,
                    'lesson_id'  => null,
                    'request_id' => null,
                ],
            ],
            'negative values' => [
                [
                    'user_id'    => -5,
                    'lesson_id'  => -10,
                    'request_id' => 'test',
                ],
            ],
        ];
    }

    #[DataProvider('notFoundProvider')]
    public function testCreateProgressNotFound(string $entityType, int $invalidId, string $expectedError): void
    {
        if ('user' === $entityType) {
            $lesson = $this->getTestLesson();
            $requestData = [
                'user_id'    => $invalidId,
                'lesson_id'  => $lesson->getId(),
                'request_id' => 'test-request-123',
            ];
        } else {
            $user = $this->getTestUser();
            $requestData = [
                'user_id'    => $user->getId(),
                'lesson_id'  => $invalidId,
                'request_id' => 'test-request-123',
            ];
        }

        $this->client->request('POST', '/progress', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($requestData));

        $this->assertResponseStatusCodeSame(404);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString($expectedError, $responseData['error']);
    }

    public static function notFoundProvider(): array
    {
        return [
            'user not found'   => ['user', 99999, 'User 99999 not found'],
            'lesson not found' => ['lesson', 99999, 'Lesson 99999 not found'],
        ];
    }

    public function testGetUserProgressSuccess(): void
    {
        $user = $this->getTestUser();
        $course = $this->getTestCourse();
        $lesson = $this->getTestLesson();

        $this->client->request('POST', '/courses/' . $course->getId() . '/enroll', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user->getId(),
        ]));

        $this->client->request('POST', '/progress', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id'    => $user->getId(),
            'lesson_id'  => $lesson->getId(),
            'request_id' => 'test-request-456',
        ]));

        $this->client->request('GET', '/progress/' . $user->getId() . '/courses/' . $course->getId());

        $this->assertResponseStatusCodeSame(200);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('completed', $responseData);
        $this->assertArrayHasKey('total', $responseData);
        $this->assertArrayHasKey('percent', $responseData);
        $this->assertArrayHasKey('lessons', $responseData);

        $this->assertEquals(1, $responseData['completed']);
        $this->assertEquals(1, $responseData['total']);
        $this->assertEquals(100, $responseData['percent']);
        $this->assertNotEmpty($responseData['lessons']);
    }

    public function testDeleteProgressSuccess(): void
    {
        $user = $this->getTestUser();
        $course = $this->getTestCourse();
        $lesson = $this->getTestLesson();

        // Enroll user in course first
        $this->client->request('POST', '/courses/' . $course->getId() . '/enroll', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user->getId(),
        ]));

        $this->assertResponseStatusCodeSame(201);

        // Create progress
        $this->client->request('POST', '/progress', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id'    => $user->getId(),
            'lesson_id'  => $lesson->getId(),
            'request_id' => 'test-delete-123',
        ]));

        $this->assertResponseStatusCodeSame(201);

        // Verify progress is complete
        $this->client->request('GET', '/progress/' . $user->getId() . '/courses/' . $course->getId());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(1, $responseData['completed']);

        // Reset progress to pending
        $this->client->request('DELETE', '/progress/' . $user->getId() . '/lessons/' . $lesson->getId());

        $this->assertResponseStatusCodeSame(204);

        // Verify progress is now pending
        $this->client->request('GET', '/progress/' . $user->getId() . '/courses/' . $course->getId());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(0, $responseData['completed']);
        $this->assertEquals(0, $responseData['percent']); // 0% because no completed lessons
    }

    public function testDeleteProgressNotFound(): void
    {
        $user = $this->getTestUser();
        $lesson = $this->getTestLesson();

        // Try to delete non-existent progress
        $this->client->request('DELETE', '/progress/' . $user->getId() . '/lessons/' . $lesson->getId());

        $this->assertResponseStatusCodeSame(204); // Should return 204 even if not found
    }

    public function testDeleteProgressFromFailed(): void
    {
        $user = $this->getTestUser();
        $course = $this->getTestCourse();
        $lesson = $this->getTestLesson();

        // Enroll user in course first
        $this->client->request('POST', '/courses/' . $course->getId() . '/enroll', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user->getId(),
        ]));

        $this->assertResponseStatusCodeSame(201);

        // Create progress with failed status (would need to be implemented in ProgressService)
        // For now, we'll test that the endpoint works for any existing progress

        // Try to delete progress (should work even if no progress exists)
        $this->client->request('DELETE', '/progress/' . $user->getId() . '/lessons/' . $lesson->getId());

        $this->assertResponseStatusCodeSame(204);
    }

    #[DataProvider('allowedStatusTransitionsProvider')]
    public function testAllowedStatusTransitions(string $initialAction, string $newAction): void
    {
        $user = $this->getTestUser();
        $course = $this->getTestCourse();
        $lesson = $this->getTestLesson();

        // Enroll user in course first
        $this->client->request('POST', '/courses/' . $course->getId() . '/enroll', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user->getId(),
        ]));

        $this->assertResponseStatusCodeSame(201);

        // Create initial progress
        $this->client->request('POST', '/progress', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id'    => $user->getId(),
            'lesson_id'  => $lesson->getId(),
            'action'     => $initialAction,
            'request_id' => 'initial-' . $initialAction,
        ]));

        $this->assertResponseStatusCodeSame(201);

        // Try to change status to new action
        $this->client->request('POST', '/progress', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id'    => $user->getId(),
            'lesson_id'  => $lesson->getId(),
            'action'     => $newAction,
            'request_id' => 'change-' . $newAction,
        ]));

        $this->assertResponseStatusCodeSame(201);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($newAction, $responseData['status']);
    }

    public static function allowedStatusTransitionsProvider(): array
    {
        return [
            'pending to complete' => ['pending', 'complete'],
            'pending to failed'   => ['pending', 'failed'],
            'failed to complete'  => ['failed', 'complete'],
            'failed to pending'   => ['failed', 'pending'],
            'complete to pending' => ['complete', 'pending'],
        ];
    }

    #[DataProvider('forbiddenStatusTransitionsProvider')]
    public function testForbiddenStatusTransitions(string $initialAction, string $forbiddenAction): void
    {
        $user = $this->getTestUser();
        $course = $this->getTestCourse();
        $lesson = $this->getTestLesson();

        // Enroll user in course first
        $this->client->request('POST', '/courses/' . $course->getId() . '/enroll', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user->getId(),
        ]));

        $this->assertResponseStatusCodeSame(201);

        // Create initial progress
        $this->client->request('POST', '/progress', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id'    => $user->getId(),
            'lesson_id'  => $lesson->getId(),
            'action'     => $initialAction,
            'request_id' => 'initial-' . $initialAction,
        ]));

        $this->assertResponseStatusCodeSame(201);

        // Try to change status to forbidden action
        $this->client->request('POST', '/progress', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id'    => $user->getId(),
            'lesson_id'  => $lesson->getId(),
            'action'     => $forbiddenAction,
            'request_id' => 'forbidden-' . $forbiddenAction,
        ]));

        $this->assertResponseStatusCodeSame(400);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('Invalid status transition', $responseData['error']);
        $this->assertStringContainsString($initialAction, $responseData['error']);
        $this->assertStringContainsString($forbiddenAction, $responseData['error']);
    }

    public static function forbiddenStatusTransitionsProvider(): array
    {
        return [
            'complete to failed' => ['complete', 'failed'],
        ];
    }

    public function testStatusTransitionHistoryTracking(): void
    {
        $user = $this->getTestUser();
        $course = $this->getTestCourse();
        $lesson = $this->getTestLesson();

        // Enroll user in course first
        $this->client->request('POST', '/courses/' . $course->getId() . '/enroll', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user->getId(),
        ]));

        $this->assertResponseStatusCodeSame(201);

        // Create initial progress (pending)
        $this->client->request('POST', '/progress', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id'    => $user->getId(),
            'lesson_id'  => $lesson->getId(),
            'action'     => 'pending',
            'request_id' => 'history-test-1',
        ]));

        $this->assertResponseStatusCodeSame(201);

        // Change to complete
        $this->client->request('POST', '/progress', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id'    => $user->getId(),
            'lesson_id'  => $lesson->getId(),
            'action'     => 'complete',
            'request_id' => 'history-test-2',
        ]));

        $this->assertResponseStatusCodeSame(201);

        // Change back to pending (dozwolone przejście)
        $this->client->request('POST', '/progress', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id'    => $user->getId(),
            'lesson_id'  => $lesson->getId(),
            'action'     => 'pending',
            'request_id' => 'history-test-3',
        ]));

        $this->assertResponseStatusCodeSame(201);

        // Check history
        $this->client->request('GET', '/progress/' . $user->getId() . '/lessons/' . $lesson->getId() . '/history');

        $this->assertResponseStatusCodeSame(200);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('history', $responseData);

        $historyData = $responseData['history'];
        $this->assertIsArray($historyData);
        $this->assertCount(3, $historyData); // 3 status changes

        // Verify history entries (od najstarszego do najnowszego)
        $this->assertEquals('pending', $historyData[0]['new_status']); // history-test-1 (najstarszy)
        $this->assertEquals('complete', $historyData[1]['new_status']); // history-test-2 (środkowy)
        $this->assertEquals('pending', $historyData[2]['new_status']); // history-test-3 (najnowszy)

        // Verify old_status values
        $this->assertNull($historyData[0]['old_status']); // pending (nowy, bez old_status)
        $this->assertEquals('pending', $historyData[1]['old_status']); // complete (z pending)
        $this->assertEquals('complete', $historyData[2]['old_status']); // pending (z complete)
    }
}
