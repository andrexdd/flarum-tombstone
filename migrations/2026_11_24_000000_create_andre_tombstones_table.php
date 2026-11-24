<?php

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTableIfNotExists('andre_tombstones', function (Blueprint $table) {
    $table->unsignedInteger('discussion_id')->primary();
    $table->string('title', 200)->nullable();
    $table->unsignedInteger('deleted_by_id')->nullable();
    $table->string('deleted_by_username', 100)->nullable();
    $table->dateTime('deleted_at');
    $table->string('redirect_url', 2000)->nullable();
    $table->string('note', 500)->nullable();
});
