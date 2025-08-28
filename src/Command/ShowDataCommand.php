<?php

namespace App\Command;

use App\Repository\Interfaces\CourseRepositoryInterface;
use App\Repository\Interfaces\EnrollmentRepositoryInterface;
use App\Repository\Interfaces\UserRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:show-data', description: 'Show all data in the database')]
class ShowDataCommand extends Command
{
    public function __construct(
        private \App\Repository\UserRepository $userRepository,
        private \App\Repository\CourseRepository $courseRepository,
        private \App\Repository\EnrollmentRepository $enrollmentRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Database Content Overview');

        // Show users
        $this->showUsers($io);
        
        // Show courses
        $this->showCourses($io);
        
        // Show enrollments
        $this->showEnrollments($io);

        return Command::SUCCESS;
    }

    private function showUsers(SymfonyStyle $io): void
    {
        $io->section('Users');
        
        $users = $this->userRepository->findAll();
        
        if (empty($users)) {
            $io->text('No users found.');
            return;
        }

        $table = [];
        foreach ($users as $user) {
            $table[] = [
                $user->getId(),
                $user->getName(),
                $user->getEmail(),
                $user->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }

        $io->table(['ID', 'Name', 'Email', 'Created At'], $table);
    }

    private function showCourses(SymfonyStyle $io): void
    {
        $io->section('Courses');
        
        $courses = $this->courseRepository->findAll();
        
        if (empty($courses)) {
            $io->text('No courses found.');
            return;
        }

        $table = [];
        foreach ($courses as $course) {
            $enrollmentCount = $this->courseRepository->countEnrollmentsByCourse($course->getId());
            $remainingSeats = $course->getMaxSeats() - $enrollmentCount;
            
            $table[] = [
                $course->getId(),
                $course->getTitle(),
                $course->getMaxSeats(),
                $enrollmentCount,
                $remainingSeats,
                $course->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }

        $io->table(['ID', 'Title', 'Max Seats', 'Enrolled', 'Available', 'Created At'], $table);
    }

    private function showEnrollments(SymfonyStyle $io): void
    {
        $io->section('Enrollments');
        
        $enrollments = $this->enrollmentRepository->findAll();
        
        if (empty($enrollments)) {
            $io->text('No enrollments found.');
            return;
        }

        $table = [];
        foreach ($enrollments as $enrollment) {
            $table[] = [
                $enrollment->getId(),
                $enrollment->getUser()->getName(),
                $enrollment->getCourse()->getTitle(),
                $enrollment->getStatus(),
                $enrollment->getEnrolledAt()->format('Y-m-d H:i:s'),
                $enrollment->getCompletedAt() ? $enrollment->getCompletedAt()->format('Y-m-d H:i:s') : 'Not completed'
            ];
        }

        $io->table(['ID', 'User', 'Course', 'Status', 'Enrolled At', 'Completed At'], $table);
    }
}
