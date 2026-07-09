<?php

namespace App\Notifications;

use App\Filament\Tenant\Resources\Tasks\TaskResource;
use App\Models\NotificationSetting;
use App\Models\Task;
use App\Models\User;
use Filament\Actions\Action as NotificationAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Task $task, public ?User $completedBy) {}

    public function via(object $notifiable): array
    {
        return NotificationSetting::enabledChannelsFor(NotificationSetting::EVENT_TASK_COMPLETED);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $actor = $this->completedBy?->name ?? 'Someone';

        return (new MailMessage)
            ->subject("Task completed: {$this->task->title}")
            ->line("{$actor} marked a task as completed in {$this->task->project->name}.")
            ->line("Task: {$this->task->title}")
            ->action('View Task', $this->taskUrl())
            ->line('Thank you for using our application!');
    }

    public function toDatabase(object $notifiable): array
    {
        $actor = $this->completedBy?->name ?? 'Someone';

        return FilamentNotification::make()
            ->title("Task completed: {$this->task->title}")
            ->body("{$actor} marked this task as completed in {$this->task->project->name}.")
            ->success()
            ->actions([
                NotificationAction::make('view')->label('View Task')->url($this->taskUrl())->markAsRead(),
            ])
            ->getDatabaseMessage() + $this->toArray($notifiable);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => NotificationSetting::EVENT_TASK_COMPLETED,
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'project_id' => $this->task->project_id,
            'project_name' => $this->task->project->name,
            'completed_by_id' => $this->completedBy?->id,
            'completed_by_name' => $this->completedBy?->name,
            'url' => $this->taskUrl(),
        ];
    }

    private function taskUrl(): string
    {
        $path = TaskResource::getUrl('view', ['record' => $this->task], isAbsolute: false, panel: 'app');

        return tenant()->url($path);
    }
}
