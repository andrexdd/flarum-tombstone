<?php

namespace Andre\Tombstone\Listener;

use Andre\Tombstone\Tombstone;
use Carbon\Carbon;
use Flarum\Discussion\Event\Deleted;

class RecordDeletedDiscussion
{
    public function handle(Deleted $event): void
    {
        $discussion = $event->discussion;

        if (! $discussion->id) {
            return;
        }

        Tombstone::updateOrCreate(
            ['discussion_id' => $discussion->id],
            [
                'title' => $discussion->title ?? null,
                'deleted_by_id' => $event->actor?->id,
                'deleted_by_username' => $event->actor?->username,
                'deleted_at' => Carbon::now(),
            ]
        );
    }
}
