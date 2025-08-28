<?php

namespace App\Tests\Feature;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversNothing
 * @group base
 * @group abstract
 */
abstract class AbstractFeature extends WebTestCase
{
    protected EntityManagerInterface $entityManager;
    protected ContainerInterface $container;
    protected $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->container = static::getContainer();
        $this->entityManager = $this->container->get(EntityManagerInterface::class);
        
        $this->cleanupDatabase();
        $this->loadTestData();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    protected function loadTestData(): void
    {
        $user = new User();
        $user->setName('Test User');
        $user->setEmail('test@example.com');
        $this->entityManager->persist($user);

        $course = new Course();
        $course->setTitle('Test Course');
        $course->setDescription('Test course description');
        $course->setMaxSeats(10);
        $this->entityManager->persist($course);

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
        $lesson = $this->entityManager->getRepository(Lesson::class)->findOneBy(['title' => 'Test Lesson']);
        return $lesson;
    }

    protected function createTestUser(string $name, string $email): User
    {
        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }

    protected function createCourseWithLimitedSeats(int $maxSeats): Course
    {
        $course = new Course();
        $course->setTitle('Limited Course');
        $course->setDescription('Course with limited seats');
        $course->setMaxSeats($maxSeats);
        $this->entityManager->persist($course);
        $this->entityManager->flush();
        
        return $course;
    }

    protected function createTestLesson(string $title, Course $course, int $orderIndex): Lesson
    {
        $lesson = new Lesson();
        $lesson->setTitle($title);
        $lesson->setContent('Test lesson content');
        $lesson->setOrderIndex($orderIndex);
        $lesson->setCourse($course);
        $this->entityManager->persist($lesson);
        $this->entityManager->flush();
        
        return $lesson;
    }

    private function cleanupDatabase(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\Progress')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Enrollment')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Lesson')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Course')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        
        $this->entityManager->flush();
    }
}
