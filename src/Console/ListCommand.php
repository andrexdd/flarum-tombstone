<?php

namespace Andre\Tombstone\Console;

use Andre\Tombstone\Tombstone;
use Flarum\Console\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;

class ListCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->setName('andre-tombstone:list')
            ->setDescription('List every discussion currently tracked as deleted, with any redirect set for it.');
    }

    protected function fire(): int
    {
        $tombstones = Tombstone::query()->orderByDesc('deleted_at')->get();

        if ($tombstones->isEmpty()) {
            $this->info('No tombstones recorded yet.');

            return Command::SUCCESS;
        }

        $rows = $tombstones->map(function (Tombstone $tombstone) {
            return [
                $tombstone->discussion_id,
                $tombstone->title ?? '(untitled)',
                $tombstone->deleted_at?->toDateTimeString(),
                $tombstone->redirect_url ?? '— 410 Gone —',
            ];
        })->all();

        $table = new Table($this->output);
        $table->setHeaders(['ID', 'Title', 'Deleted at', 'Behavior']);
        $table->setRows($rows);
        $table->render();

        return Command::SUCCESS;
    }
}
