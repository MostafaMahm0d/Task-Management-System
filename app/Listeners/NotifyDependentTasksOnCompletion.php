<?php

namespace App\Listeners;

use App\Events\TaskCompleted;
use App\Notifications\TaskUnblockedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyDependentTasksOnCompletion implements ShouldQueue
{
    public bool $deleteWhenMissingModels = true;

    public function handle(TaskCompleted $event): void
    {
        $event->task->blocks()->get()->each(function ($dependentTask) use ($event): void {
            if ($dependentTask->isBlocked()) {
                return;
            }

            $dependentTask->assignee?->notify(new TaskUnblockedNotification($dependentTask, $event->task));
        });
    }
}
