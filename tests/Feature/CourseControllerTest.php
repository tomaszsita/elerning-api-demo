<?php

namespace App\Tests\Feature;

use App\Entity\Course;
use App\Entity\User;

class CourseControllerTest extends BaseFeatureTest
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

    public function testEnrollInCourseInvalidJson(): void
    {
        $course = $this->getTestCourse();

        $this->client->request('POST', '/courses/' . $course->getId() . '/enroll', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], 'invalid json');

        $this->assertResponseStatusCodeSame(400);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Invalid JSON', $responseData['error']);
    }

    public function testEnrollInCourseMissingUserId(): void
    {
        $course = $this->getTestCourse();

        $this->client->request('POST', '/courses/' . $course->getId() . '/enroll', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(400);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('user_id is required', $responseData['error']);
    }

    public function testEnrollInCourseInvalidUserId(): void
    {
        $course = $this->getTestCourse();

        $this->client->request('POST', '/courses/' . $course->getId() . '/enroll', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => 'invalid',
        ]));

        $this->assertResponseStatusCodeSame(400);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('user_id is required and must be numeric', $responseData['error']);
    }

    public function testEnrollInCourseUserNotFound(): void
    {
        $course = $this->getTestCourse();

        $this->client->request('POST', '/courses/' . $course->getId() . '/enroll', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => 99999,
        ]));

        $this->assertResponseStatusCodeSame(404);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('User 99999 not found', $responseData['error']);
    }

    public function testEnrollInCourseNotFound(): void
    {
        $user = $this->getTestUser();

        $this->client->request('POST', '/courses/99999/enroll', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user->getId(),
        ]));

        $this->assertResponseStatusCodeSame(404);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('Course 99999 not found', $responseData['error']);
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
}
