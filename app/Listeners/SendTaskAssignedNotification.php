<?php

namespace App\Listeners;

use App\Events\TaskAssigned;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTaskAssignedNotification implements ShouldQueue
{
    public bool $deleteWhenMissingModels = true;

    public function handle(TaskAssigned $event): void
    {
        $event->task->assignee?->notify(new TaskAssignedNotification($event->task));
    }
}
