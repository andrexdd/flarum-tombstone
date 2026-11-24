<?php

namespace Andre\Tombstone;

use Flarum\Database\AbstractModel;

class Tombstone extends AbstractModel
{
    protected $table = 'andre_tombstones';

    protected $primaryKey = 'discussion_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $casts = [
        'discussion_id' => 'integer',
        'deleted_by_id' => 'integer',
        'deleted_at' => 'datetime',
    ];

    protected $fillable = [
        'discussion_id',
        'title',
        'deleted_by_id',
        'deleted_by_username',
        'deleted_at',
        'redirect_url',
        'note',
    ];

    public static function findForDiscussion(int $discussionId): ?self
    {
        return static::query()->find($discussionId);
    }
}
