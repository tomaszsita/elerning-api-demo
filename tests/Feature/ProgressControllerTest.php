<?php

namespace App\Tests\Feature;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\User;

class ProgressControllerTest extends AbstractFeatureTest
{
    public function testCreateProgressSuccess(): void
    {
        $user = $this->getTestUser();
        $course = $this->getTestCourse();
        $lesson = $this->getTestLesson();

        $this->client->request('POST', '/enrollments', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user->getId(),
            'course_id' => $course->getId(),
        ]));

        $this->client->request('POST', '/progress', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user->getId(),
            'lesson_id' => $lesson->getId(),
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

        $this->client->request('POST', '/enrollments', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user->getId(),
            'course_id' => $course->getId(),
        ]));

        $this->client->request('POST', '/progress', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user->getId(),
            'lesson_id' => $lesson->getId(),
            'request_id' => $requestId,
        ]));

        $this->assertResponseStatusCodeSame(201);
        $firstResponse = json_decode($this->client->getResponse()->getContent(), true);


        $this->client->request('POST', '/progress', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user->getId(),
            'lesson_id' => $lesson->getId(),
            'request_id' => $requestId,
        ]));

        $this->assertResponseStatusCodeSame(201);
        $secondResponse = json_decode($this->client->getResponse()->getContent(), true);


        $this->assertEquals($firstResponse['id'], $secondResponse['id']);
        $this->assertEquals($firstResponse['request_id'], $secondResponse['request_id']);
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

    public function testCreateProgressValidationErrors(): void
    {
        $this->client->request('POST', '/progress', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => 0,
            'lesson_id' => -1,
            'request_id' => '',
        ]));

        $this->assertResponseStatusCodeSame(400);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertNotEmpty($responseData['errors']);
    }

    public function testCreateProgressUserNotFound(): void
    {
        $lesson = $this->getTestLesson();

        $this->client->request('POST', '/progress', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => 99999,
            'lesson_id' => $lesson->getId(),
            'request_id' => 'test-request-123',
        ]));

        $this->assertResponseStatusCodeSame(404);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('User 99999 not found', $responseData['error']);
    }

    public function testCreateProgressLessonNotFound(): void
    {
        $user = $this->getTestUser();

        $this->client->request('POST', '/progress', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user->getId(),
            'lesson_id' => 99999,
            'request_id' => 'test-request-123',
        ]));

        $this->assertResponseStatusCodeSame(404);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('Lesson 99999 not found', $responseData['error']);
    }

    public function testGetUserProgressSuccess(): void
    {
        $user = $this->getTestUser();
        $course = $this->getTestCourse();
        $lesson = $this->getTestLesson();

        $this->client->request('POST', '/enrollments', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user->getId(),
            'course_id' => $course->getId(),
        ]));

        $this->client->request('POST', '/progress', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user->getId(),
            'lesson_id' => $lesson->getId(),
            'request_id' => 'test-request-456',
        ]));


        $this->client->request('GET', '/progress?user_id=' . $user->getId() . '&course_id=' . $course->getId());

        $this->assertResponseStatusCodeSame(200);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('progress', $responseData);
        $this->assertNotEmpty($responseData['progress']);
        
        $progress = $responseData['progress'][0];
        $this->assertEquals($user->getId(), $progress['user_id']);
        $this->assertEquals($lesson->getId(), $progress['lesson_id']);
        $this->assertEquals('Test Lesson', $progress['lesson_title']);
        $this->assertEquals('complete', $progress['status']);
        $this->assertEquals('test-request-456', $progress['request_id']);
    }

    public function testGetUserProgressMissingUserId(): void
    {
        $course = $this->getTestCourse();

        $this->client->request('GET', '/progress?course_id=' . $course->getId());

        $this->assertResponseStatusCodeSame(400);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('user_id parameter is required', $responseData['error']);
    }

    public function testGetUserProgressMissingCourseId(): void
    {
        $user = $this->getTestUser();

        $this->client->request('GET', '/progress?user_id=' . $user->getId());

        $this->assertResponseStatusCodeSame(400);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('course_id parameter is required', $responseData['error']);
    }

    public function testGetUserProgressInvalidUserId(): void
    {
        $course = $this->getTestCourse();

        $this->client->request('GET', '/progress?user_id=invalid&course_id=' . $course->getId());

        $this->assertResponseStatusCodeSame(400);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('user_id parameter is required and must be numeric', $responseData['error']);
    }

    public function testGetUserProgressInvalidCourseId(): void
    {
        $user = $this->getTestUser();

        $this->client->request('GET', '/progress?user_id=' . $user->getId() . '&course_id=invalid');

        $this->assertResponseStatusCodeSame(400);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('course_id parameter is required and must be numeric', $responseData['error']);
    }
}
