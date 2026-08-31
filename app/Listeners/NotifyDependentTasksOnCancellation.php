<?php

namespace App\Listeners;

use App\Events\TaskCancelled;
use App\Notifications\TaskUnblockedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyDependentTasksOnCancellation implements ShouldQueue
{
    public bool $deleteWhenMissingModels = true;

    public function handle(TaskCancelled $event): void
    {
        $event->task->blocks()->get()->each(function ($dependentTask) use ($event): void {
            if ($dependentTask->isBlocked()) {
                return;
            }

            $dependentTask->assignee?->notify(new TaskUnblockedNotification($dependentTask, $event->task, 'cancelled'));
        });
    }
}
