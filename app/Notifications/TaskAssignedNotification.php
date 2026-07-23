<?php

namespace App\Notifications;

use App\Filament\Tenant\Resources\Projects\Resources\Tasks\TaskResource;
use App\Models\NotificationSetting;
use App\Models\Task;
use Filament\Actions\Action as NotificationAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Task $task) {}

    public function via(object $notifiable): array
    {
        return NotificationSetting::enabledChannelsFor(NotificationSetting::EVENT_TASK_ASSIGNED);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("You've been assigned: {$this->task->title}")
            ->line("You have been assigned a new task in {$this->task->project->name}.")
            ->line("Task: {$this->task->title}")
            ->when($this->task->due_date, fn (MailMessage $mail) => $mail->line("Due: {$this->task->due_date->format('M j, Y')}"))
            ->action('View Task', $this->taskUrl())
            ->line('Thank you for using our application!');
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title("You've been assigned: {$this->task->title}")
            ->body("Project: {$this->task->project->name}")
            ->info()
            ->actions([
                NotificationAction::make('view')->label('View Task')->url($this->taskUrl())->markAsRead(),
            ])
            ->getDatabaseMessage() + $this->toArray($notifiable);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => NotificationSetting::EVENT_TASK_ASSIGNED,
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'project_id' => $this->task->project_id,
            'project_name' => $this->task->project->name,
            'url' => $this->taskUrl(),
        ];
    }

    private function taskUrl(): string
    {
        $path = TaskResource::getUrl('view', ['project' => $this->task->project, 'record' => $this->task], isAbsolute: false, panel: 'app');

        return tenant()->url($path);
    }
}
