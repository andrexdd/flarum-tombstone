<?php

use Andre\Tombstone\Console\ForgetCommand;
use Andre\Tombstone\Console\ListCommand;
use Andre\Tombstone\Console\SetRedirectCommand;
use Andre\Tombstone\Listener\RecordDeletedDiscussion;
use Andre\Tombstone\Middleware\RedirectGoneDiscussions;
use Flarum\Discussion\Event\Deleted;
use Flarum\Extend;

return [
    (new Extend\Event())
        ->listen(Deleted::class, RecordDeletedDiscussion::class),

    (new Extend\Middleware('forum'))
        ->add(RedirectGoneDiscussions::class),

    (new Extend\Middleware('api'))
        ->add(RedirectGoneDiscussions::class),

    (new Extend\Console())
        ->command(SetRedirectCommand::class)
        ->command(ForgetCommand::class)
        ->command(ListCommand::class),
];
