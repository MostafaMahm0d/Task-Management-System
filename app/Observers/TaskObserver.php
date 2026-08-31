<?php

namespace App\Observers;

use App\Events\TaskAssigned;
use App\Events\TaskCancelled;
use App\Events\TaskCompleted;
use App\Models\Task;

class TaskObserver
{
    public function created(Task $task): void
    {
        if ($task->assignee_id !== null) {
            event(new TaskAssigned($task));
        }
    }

    public function updated(Task $task): void
    {
        if ($task->isDirty('assignee_id') && $task->assignee_id !== null) {
            event(new TaskAssigned($task));
        }

        if ($task->isDirty('status_id') && $task->status?->is_completed) {
            event(new TaskCompleted($task, auth()->user()));
        }

        if ($task->isDirty('status_id') && $task->status?->is_cancelled) {
            event(new TaskCancelled($task, auth()->user()));
        }

        if ($task->isDirty('status_id')) {
            $assignee = $task->status?->assignmentRule?->resolveAssigneeFor($task);

            if ($assignee && $assignee->id !== $task->assignee_id) {
                $task->update(['assignee_id' => $assignee->id]);
            }
        }

        if ($task->isDirty('due_date') && $task->overdue_notified_at !== null) {
            $task->forceFill(['overdue_notified_at' => null])->saveQuietly();
        }
    }
}
