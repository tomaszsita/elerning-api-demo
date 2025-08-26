<?php

namespace App\Command;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\User;
use App\Repository\Interfaces\CourseRepositoryInterface;
use App\Repository\Interfaces\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:load-test-data', description: 'Load test data for the e-learning platform')]
class LoadTestDataCommand extends Command
{
    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    private EntityManagerInterface $entityManager;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Loading test data for e-learning platform');

        try {
            // Create users
            $users = $this->createUsers($io);
            
            // Create courses with lessons
            $courses = $this->createCourses($io);
            
            $this->entityManager->flush();
            
            $io->success([
                'Test data loaded successfully!',
                sprintf('Created %d users', count($users)),
                sprintf('Created %d courses with lessons', count($courses))
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to load test data: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * @return User[]
     */
    private function createUsers(SymfonyStyle $io): array
    {
        $io->section('Creating users...');
        
        $users = [];
        $userData = [
            ['email' => 'john.doe@example.com', 'name' => 'John Doe'],
            ['email' => 'jane.smith@example.com', 'name' => 'Jane Smith'],
            ['email' => 'bob.wilson@example.com', 'name' => 'Bob Wilson'],
            ['email' => 'alice.johnson@example.com', 'name' => 'Alice Johnson'],
        ];

        foreach ($userData as $data) {
            $user = new User();
            $user->setEmail($data['email']);
            $user->setName($data['name']);
            
            $this->entityManager->persist($user);
            $users[] = $user;
            
            $io->text(sprintf('Created user: %s (%s)', $data['name'], $data['email']));
        }

        return $users;
    }

    /**
     * @return Course[]
     */
    private function createCourses(SymfonyStyle $io): array
    {
        $io->section('Creating courses with lessons...');
        
        $courses = [];
        $courseData = [
            [
                'title' => 'PHP Fundamentals',
                'description' => 'Learn the basics of PHP programming',
                'maxSeats' => 20,
                'lessons' => [
                    ['title' => 'Introduction to PHP', 'content' => 'PHP is a server-side scripting language...'],
                    ['title' => 'Variables and Data Types', 'content' => 'PHP supports various data types...'],
                    ['title' => 'Control Structures', 'content' => 'Learn about if, else, loops...'],
                ]
            ],
            [
                'title' => 'Symfony Framework',
                'description' => 'Master the Symfony PHP framework',
                'maxSeats' => 15,
                'lessons' => [
                    ['title' => 'Symfony Overview', 'content' => 'Symfony is a PHP framework...'],
                    ['title' => 'Routing and Controllers', 'content' => 'Learn about routing...'],
                    ['title' => 'Doctrine ORM', 'content' => 'Database management with Doctrine...'],
                    ['title' => 'Forms and Validation', 'content' => 'Handle forms and validation...'],
                ]
            ],
            [
                'title' => 'Database Design',
                'description' => 'Learn database design principles',
                'maxSeats' => 25,
                'lessons' => [
                    ['title' => 'Database Fundamentals', 'content' => 'Understanding databases...'],
                    ['title' => 'Normalization', 'content' => 'Database normalization rules...'],
                    ['title' => 'SQL Basics', 'content' => 'Structured Query Language...'],
                ]
            ]
        ];

        foreach ($courseData as $data) {
            $course = new Course();
            $course->setTitle($data['title']);
            $course->setDescription($data['description']);
            $course->setMaxSeats($data['maxSeats']);
            
            $this->entityManager->persist($course);
            
            // Create lessons for this course
            foreach ($data['lessons'] as $index => $lessonData) {
                $lesson = new Lesson();
                $lesson->setTitle($lessonData['title']);
                $lesson->setContent($lessonData['content']);
                $lesson->setOrderIndex($index + 1);
                $lesson->setCourse($course);
                
                $this->entityManager->persist($lesson);
            }
            
            $courses[] = $course;
            
            $io->text(sprintf(
                'Created course: %s (%d lessons, %d max seats)',
                $data['title'],
                count($data['lessons']),
                $data['maxSeats']
            ));
        }

        return $courses;
    }
}
