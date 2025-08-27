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
            $summary = $this->progressService->getUserProgressSummary($userId, $courseId);
            
            $io->text(sprintf('%d/%d (%d%%)', $summary['completed'], $summary['total'], $summary['percent']));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
