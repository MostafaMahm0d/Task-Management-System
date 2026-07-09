<?php

namespace App\Listeners;

use App\Events\TaskCompleted;
use App\Notifications\TaskCompletedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTaskCompletedNotification implements ShouldQueue
{
    public function handle(TaskCompleted $event): void
    {
        collect([$event->task->reporter, $event->task->project->owner])
            ->filter()
            ->unique('id')
            ->reject(fn ($user) => $event->completedBy && $user->id === $event->completedBy->id)
            ->each(fn ($user) => $user->notify(new TaskCompletedNotification($event->task, $event->completedBy)));
    }
}
