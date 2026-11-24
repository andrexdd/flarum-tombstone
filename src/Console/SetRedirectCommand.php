<?php

namespace Andre\Tombstone\Console;

use Andre\Tombstone\Tombstone;
use Carbon\Carbon;
use Flarum\Console\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class SetRedirectCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->setName('andre-tombstone:redirect')
            ->setDescription('Permanently redirect visitors of a deleted discussion to another URL.')
            ->addArgument('id', InputArgument::REQUIRED, 'The ID of the deleted discussion.')
            ->addArgument('url', InputArgument::REQUIRED, 'The absolute URL or path visitors should be redirected to.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Create the tombstone record even if this discussion ID was never recorded as deleted.');
    }

    protected function fire(): int
    {
        $id = (int) $this->input->getArgument('id');
        $url = $this->input->getArgument('url');

        if ($id <= 0) {
            $this->error('The discussion ID must be a positive integer.');

            return Command::INVALID;
        }

        if (! filter_var($url, FILTER_VALIDATE_URL) && ! str_starts_with($url, '/')) {
            $this->error("'$url' doesn't look like a valid URL or an absolute path starting with '/'.");

            return Command::INVALID;
        }

        $tombstone = Tombstone::findForDiscussion($id);

        if ($tombstone === null) {
            if (! $this->input->getOption('force')) {
                $this->error("Discussion #$id has no tombstone record. It may still exist, or it was deleted before this extension was installed. Pass --force to create one anyway.");

                return Command::FAILURE;
            }

            $tombstone = new Tombstone();
            $tombstone->discussion_id = $id;
            $tombstone->deleted_at = Carbon::now();
        }

        $tombstone->redirect_url = $url;
        $tombstone->save();

        $this->info("Discussion #$id will now permanently redirect to: $url");

        return Command::SUCCESS;
    }
}
