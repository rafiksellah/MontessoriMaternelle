<?php

namespace App\Command;

use App\Service\ReminderEmailService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-event-reminder',
    description: 'Send reminder emails for upcoming event',
)]
class SendEventReminderCommand extends Command
{
    private $reminderEmailService;

    public function __construct(ReminderEmailService $reminderEmailService)
    {
        parent::__construct();
        $this->reminderEmailService = $reminderEmailService;
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command sends reminder emails to registered participants 5 days before the event');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Sending event reminder emails');

        try {
            $sentCount = $this->reminderEmailService->sendReminderEmails();
            
            if ($sentCount > 0) {
                $io->success("Successfully sent {$sentCount} reminder emails.");
            } else {
                $io->info("No reminder emails were sent. It's either not 5 days before the event or all reminders have already been sent.");
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error("Error sending reminder emails: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}