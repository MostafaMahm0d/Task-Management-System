<?php

namespace App\Notifications;

use App\Filament\Tenant\Resources\Tasks\TaskResource;
use App\Models\NotificationSetting;
use App\Models\Task;
use Filament\Actions\Action as NotificationAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskUnblockedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Task $task, public Task $dependency, public string $reason = 'completed') {}

    public function via(object $notifiable): array
    {
        return NotificationSetting::enabledChannelsFor(NotificationSetting::EVENT_TASK_UNBLOCKED);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Task unblocked: {$this->task->title}")
            ->line("\"{$this->dependency->title}\" {$this->reasonPhrase()}, so this task is no longer blocked.")
            ->line("Task: {$this->task->title}")
            ->action('View Task', $this->taskUrl())
            ->line('Thank you for using our application!');
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title("Task unblocked: {$this->task->title}")
            ->body("\"{$this->dependency->title}\" {$this->reasonPhrase()} — this task is no longer blocked.")
            ->success()
            ->actions([
                NotificationAction::make('view')->label('View Task')->url($this->taskUrl())->markAsRead(),
            ])
            ->getDatabaseMessage() + $this->toArray($notifiable);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => NotificationSetting::EVENT_TASK_UNBLOCKED,
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'project_id' => $this->task->project_id,
            'project_name' => $this->task->project->name,
            'reason' => $this->reason,
            'dependency_id' => $this->dependency->id,
            'dependency_title' => $this->dependency->title,
            'url' => $this->taskUrl(),
        ];
    }

    private function reasonPhrase(): string
    {
        return $this->reason === 'cancelled' ? 'was cancelled' : 'is now complete';
    }

    private function taskUrl(): string
    {
        $path = TaskResource::getUrl('view', ['record' => $this->task], isAbsolute: false, panel: 'app');

        return tenant()->url($path);
    }
}
