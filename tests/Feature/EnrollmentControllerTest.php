<?php

namespace App\Tests\Feature;

use App\Entity\Course;
use App\Entity\User;

class EnrollmentControllerTest extends BaseFeatureTest
{
    public function testCreateEnrollmentSuccess(): void
    {
        $user = $this->getTestUser();
        $course = $this->getTestCourse();

        $this->client->request('POST', '/enrollments', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user->getId(),
            'course_id' => $course->getId(),
        ]));

        $this->assertResponseStatusCodeSame(201);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals($user->getId(), $responseData['user_id']);
        $this->assertEquals($course->getId(), $responseData['course_id']);
        $this->assertEquals('enrolled', $responseData['status']);
        $this->assertArrayHasKey('enrolled_at', $responseData);
    }

    public function testCreateEnrollmentInvalidJson(): void
    {
        $this->client->request('POST', '/enrollments', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], 'invalid json');

        $this->assertResponseStatusCodeSame(400);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Invalid JSON', $responseData['error']);
    }

    public function testCreateEnrollmentValidationErrors(): void
    {
        $this->client->request('POST', '/enrollments', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => 0,
            'course_id' => -1,
        ]));

        $this->assertResponseStatusCodeSame(400);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertNotEmpty($responseData['errors']);
    }

    public function testCreateEnrollmentUserNotFound(): void
    {
        $course = $this->getTestCourse();

        $this->client->request('POST', '/enrollments', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => 99999,
            'course_id' => $course->getId(),
        ]));

        $this->assertResponseStatusCodeSame(404);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('User 99999 not found', $responseData['error']);
    }

    public function testCreateEnrollmentCourseNotFound(): void
    {
        $user = $this->getTestUser();

        $this->client->request('POST', '/enrollments', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user->getId(),
            'course_id' => 99999,
        ]));

        $this->assertResponseStatusCodeSame(404);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('Course 99999 not found', $responseData['error']);
    }

    public function testGetUserEnrollmentsSuccess(): void
    {
        $user = $this->getTestUser();
        $course = $this->getTestCourse();

        $this->client->request('POST', '/enrollments', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user->getId(),
            'course_id' => $course->getId(),
        ]));

        $this->client->request('GET', '/enrollments?user_id=' . $user->getId());
        $this->client->request('GET', '/enrollments?user_id=' . $user->getId());

        $this->assertResponseStatusCodeSame(200);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('enrollments', $responseData);
        $this->assertNotEmpty($responseData['enrollments']);
        
        $enrollment = $responseData['enrollments'][0];
        $this->assertEquals($user->getId(), $enrollment['user_id']);
        $this->assertEquals($course->getId(), $enrollment['course_id']);
        $this->assertEquals('Test Course', $enrollment['course_title']);
        $this->assertEquals('enrolled', $enrollment['status']);
    }

    public function testGetUserEnrollmentsMissingUserId(): void
    {
        $this->client->request('GET', '/enrollments');

        $this->assertResponseStatusCodeSame(400);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('user_id parameter is required', $responseData['error']);
    }

    public function testGetUserEnrollmentsInvalidUserId(): void
    {
        $this->client->request('GET', '/enrollments?user_id=invalid');

        $this->assertResponseStatusCodeSame(400);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('user_id parameter is required and must be numeric', $responseData['error']);
    }
}
