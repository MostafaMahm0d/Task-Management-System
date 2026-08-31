<?php

namespace App\Console\Commands\Tasks;

use App\Models\Task;
use App\Models\Tenant;
use App\Notifications\TaskOverdueNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tasks:notify-overdue')]
#[Description('Notify assignees of tasks whose due date has passed and are not yet completed')]
class NotifyOverdueTasksCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Tenant::all()->each(function (Tenant $tenant): void {
            $tenant->run(function () use ($tenant) {
                $tasks = Task::query()
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', now())
                    ->whereNull('overdue_notified_at')
                    ->whereNotNull('assignee_id')
                    ->whereHas('status', fn ($query) => $query->where('is_completed', false))
                    ->with('assignee')
                    ->get();

                foreach ($tasks as $task) {
                    $task->assignee?->notify(new TaskOverdueNotification($task));
                    $task->forceFill(['overdue_notified_at' => now()])->save();
                }

                if ($tasks->isNotEmpty()) {
                    $this->line("  [{$tenant->id}] notified {$tasks->count()} overdue task(s)");
                }
            });
        });
    }
}
