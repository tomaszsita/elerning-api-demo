<?php

declare(strict_types = 1);

namespace App\Tests\Feature;

use App\Entity\Course;
use App\Entity\User;

class UserControllerTest extends AbstractFeature
{
    public function testGetUserCoursesSuccess(): void
    {
        $user = $this->getTestUser();
        $course = $this->getTestCourse();

        // Enroll user in course first
        $this->client->request('POST', '/courses/' . $course->getId() . '/enroll', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'user_id' => $user->getId(),
        ]));

        $this->assertResponseStatusCodeSame(201);

        // Get user courses
        $this->client->request('GET', '/users/' . $user->getId() . '/courses');

        $this->assertResponseStatusCodeSame(200);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('courses', $responseData);
        $this->assertNotEmpty($responseData['courses']);

        $courseData = $responseData['courses'][0];
        $this->assertArrayHasKey('id', $courseData);
        $this->assertArrayHasKey('title', $courseData);
        $this->assertArrayHasKey('description', $courseData);
        $this->assertArrayHasKey('max_seats', $courseData);
        $this->assertEquals($course->getId(), $courseData['id']);
    }

    public function testGetUserCoursesUserNotFound(): void
    {
        $this->client->request('GET', '/users/99999/courses');

        $this->assertResponseStatusCodeSame(404);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('User 99999 not found', $responseData['error']);
    }

    public function testGetUserCoursesEmpty(): void
    {
        $user = $this->getTestUser();

        $this->client->request('GET', '/users/' . $user->getId() . '/courses');

        $this->assertResponseStatusCodeSame(200);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('courses', $responseData);
        $this->assertEmpty($responseData['courses']);
    }
}
