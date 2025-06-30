<?php

namespace App\Command;

use App\Repository\ContactRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:clean-suspicious-contacts',
    description: 'Clean suspicious contact entries'
)]
class CleanSuspiciousContactsCommand extends Command
{
    public function __construct(
        private ContactRepository $contactRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Supprimer les contacts avec des noms suspects
        $suspiciousContacts = $this->contactRepository->findSuspiciousContacts();

        $count = 0;
        foreach ($suspiciousContacts as $contact) {
            $this->contactRepository->remove($contact, true);
            $count++;
        }

        $io->success(sprintf('Cleaned %d suspicious contacts', $count));

        return Command::SUCCESS;
    }
}
