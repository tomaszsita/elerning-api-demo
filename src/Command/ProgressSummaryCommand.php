<?php

namespace App\Command;

use App\Service\ProgressService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'progress:summary', description: 'Show progress summary for a user in a course')]
class ProgressSummaryCommand extends Command
{
    private ProgressService $progressService;

    public function __construct(ProgressService $progressService)
    {
        parent::__construct();
        $this->progressService = $progressService;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('userId', InputArgument::REQUIRED, 'User ID')
            ->addArgument('courseId', InputArgument::REQUIRED, 'Course ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $userId = (int) $input->getArgument('userId');
        $courseId = (int) $input->getArgument('courseId');

        try {
            $course = $this->progressService->getCourse($courseId);
            $progressList = $this->progressService->getUserProgress($userId, $courseId);
            
            $totalLessons = count($course->getLessons());
            $completedLessons = count(array_filter($progressList, fn($p) => $p->getStatus()->value === 'complete'));
            $percent = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

            $io->text(sprintf('%d/%d (%d%%)', $completedLessons, $totalLessons, $percent));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
