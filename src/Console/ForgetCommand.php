<?php

namespace Andre\Tombstone\Console;

use Andre\Tombstone\Tombstone;
use Flarum\Console\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

class ForgetCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->setName('andre-tombstone:forget')
            ->setDescription('Remove a tombstone record, restoring the plain 404 for that discussion ID.')
            ->addArgument('id', InputArgument::REQUIRED, 'The ID of the discussion to forget.');
    }

    protected function fire(): int
    {
        $id = (int) $this->input->getArgument('id');

        $tombstone = Tombstone::findForDiscussion($id);

        if ($tombstone === null) {
            $this->error("Discussion #$id has no tombstone record.");

            return Command::FAILURE;
        }

        $tombstone->delete();

        $this->info("Tombstone for discussion #$id has been removed.");

        return Command::SUCCESS;
    }
}
