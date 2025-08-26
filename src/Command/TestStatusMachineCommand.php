<?php

namespace App\Command;

use App\Enum\ProgressStatus;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:test-status-machine', description: 'Test the progress status state machine')]
class TestStatusMachineCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Testing Progress Status State Machine');

        // Test valid transitions
        $this->testValidTransitions($io);
        
        // Test invalid transitions
        $this->testInvalidTransitions($io);
        
        // Test allowed transitions for each status
        $this->testAllowedTransitions($io);

        return Command::SUCCESS;
    }

    private function testValidTransitions(SymfonyStyle $io): void
    {
        $io->section('Testing Valid Transitions');

        $validTransitions = [
            [ProgressStatus::PENDING, ProgressStatus::COMPLETE, true],
            [ProgressStatus::PENDING, ProgressStatus::FAILED, true],
            [ProgressStatus::FAILED, ProgressStatus::COMPLETE, true],
        ];

        foreach ($validTransitions as [$from, $to, $expected]) {
            $result = ProgressStatus::canTransition($from, $to);
            $status = $result === $expected ? '✅' : '❌';
            $io->text(sprintf('%s %s -> %s: %s', $status, $from, $to, $result ? 'ALLOWED' : 'DENIED'));
        }
    }

    private function testInvalidTransitions(SymfonyStyle $io): void
    {
        $io->section('Testing Invalid Transitions');

        $invalidTransitions = [
            [ProgressStatus::COMPLETE, ProgressStatus::PENDING, false],
            [ProgressStatus::COMPLETE, ProgressStatus::FAILED, false],
            [ProgressStatus::FAILED, ProgressStatus::PENDING, false],
            [ProgressStatus::PENDING, ProgressStatus::PENDING, false],
        ];

        foreach ($invalidTransitions as [$from, $to, $expected]) {
            $result = ProgressStatus::canTransition($from, $to);
            $status = $result === $expected ? '✅' : '❌';
            $io->text(sprintf('%s %s -> %s: %s', $status, $from, $to, $result ? 'ALLOWED' : 'DENIED'));
        }
    }

    private function testAllowedTransitions(SymfonyStyle $io): void
    {
        $io->section('Testing Allowed Transitions for Each Status');

        $statuses = [ProgressStatus::PENDING, ProgressStatus::FAILED, ProgressStatus::COMPLETE];

        foreach ($statuses as $status) {
            $allowed = ProgressStatus::getAllowedTransitions($status);
            $isFinal = ProgressStatus::isFinal($status);
            
            $io->text(sprintf(
                'Status: %s | Allowed: [%s] | Final: %s',
                $status,
                implode(', ', $allowed),
                $isFinal ? 'Yes' : 'No'
            ));
        }
    }
}
