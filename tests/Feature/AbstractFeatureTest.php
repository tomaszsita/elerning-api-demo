<?php

namespace App\Tests\Feature;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractFeatureTest extends WebTestCase
{
    protected EntityManagerInterface $entityManager;
    protected ContainerInterface $container;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->container = static::getContainer();
        $this->entityManager = $this->container->get(EntityManagerInterface::class);
        
        // Start transaction
        $this->entityManager->beginTransaction();
        
        // Load test data
        $this->loadTestData();
    }

    protected function tearDown(): void
    {
        // Rollback transaction
        if ($this->entityManager->isOpen()) {
            $this->entityManager->rollback();
        }
        
        // Reset client
        $this->client = null;
    }

    protected function loadTestData(): void
    {
        // Create test user
        $user = new User();
        $user->setName('Test User');
        $user->setEmail('test@example.com');
        $this->entityManager->persist($user);

        // Create test course
        $course = new Course();
        $course->setTitle('Test Course');
        $course->setDescription('Test course description');
        $course->setMaxSeats(10);
        $this->entityManager->persist($course);

        // Create test lesson
        $lesson = new Lesson();
        $lesson->setTitle('Test Lesson');
        $lesson->setContent('Test lesson content');
        $lesson->setOrderIndex(1);
        $lesson->setCourse($course);
        $this->entityManager->persist($lesson);

        $this->entityManager->flush();
    }

    protected function getTestUser(): User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);
    }

    protected function getTestCourse(): Course
    {
        return $this->entityManager->getRepository(Course::class)->findOneBy(['title' => 'Test Course']);
    }

    protected function getTestLesson(): Lesson
    {
        return $this->entityManager->getRepository(Lesson::class)->findOneBy(['title' => 'Test Lesson']);
    }
}
