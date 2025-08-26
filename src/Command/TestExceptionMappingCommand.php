<?php

namespace App\Command;

use App\Exception\HttpExceptionMapping;
use App\Exception\InvalidStatusTransitionException;
use App\Exception\UserNotFoundException;
use App\Exception\CourseFullException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:test-exception-mapping', description: 'Test exception to HTTP status code mapping')]
class TestExceptionMappingCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Testing Exception to HTTP Status Code Mapping');

        $testCases = [
            new InvalidStatusTransitionException('pending', 'invalid'),
            new UserNotFoundException(123),
            new CourseFullException(456),
            new \Exception('Generic exception'),
        ];

        foreach ($testCases as $exception) {
            $statusCode = HttpExceptionMapping::getStatusCode($exception);
            $message = HttpExceptionMapping::getErrorMessage($exception);
            
            $io->text(sprintf(
                'Exception: %s | Status: %d | Message: %s',
                get_class($exception),
                $statusCode,
                $message
            ));
        }

        return Command::SUCCESS;
    }
}
