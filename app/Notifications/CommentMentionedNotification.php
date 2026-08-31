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
use Relaticle\Comments\Models\Comment;

class CommentMentionedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Comment $comment) {}

    public function via(object $notifiable): array
    {
        return NotificationSetting::enabledChannelsFor(NotificationSetting::EVENT_COMMENT_MENTIONED);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $commenterName = $this->comment->commenter->name ?? 'Someone';
        $commentableLabel = $this->commentableLabel();

        $mail = (new MailMessage)
            ->subject("{$commenterName} mentioned you in a comment")
            ->line("{$commenterName} mentioned you in a comment{$commentableLabel}.")
            ->line(strip_tags($this->comment->body));

        if ($url = $this->commentableUrl()) {
            $mail->action('View Task', $url);
        }

        return $mail->line('Thank you for using our application!');
    }

    public function toDatabase(object $notifiable): array
    {
        $commenterName = $this->comment->commenter->name ?? 'Someone';

        $actions = [];

        if ($url = $this->commentableUrl()) {
            $actions[] = NotificationAction::make('view')->label('View Task')->url($url)->markAsRead();
        }

        return FilamentNotification::make()
            ->title("{$commenterName} mentioned you in a comment")
            ->body(strip_tags($this->comment->body))
            ->info()
            ->actions($actions)
            ->getDatabaseMessage() + $this->toArray($notifiable);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => NotificationSetting::EVENT_COMMENT_MENTIONED,
            'comment_id' => $this->comment->id,
            'commenter_id' => $this->comment->commenter_id,
            'commenter_name' => $this->comment->commenter->name ?? null,
            'commentable_type' => $this->comment->commentable_type,
            'commentable_id' => $this->comment->commentable_id,
            'body' => strip_tags($this->comment->body),
            'url' => $this->commentableUrl(),
        ];
    }

    private function commentableUrl(): ?string
    {
        $commentable = $this->comment->commentable;

        if (! $commentable instanceof Task) {
            return null;
        }

        $path = TaskResource::getUrl('view', ['project' => $commentable->project, 'record' => $commentable], isAbsolute: false, panel: 'app');

        return tenant()->url($path);
    }

    private function commentableLabel(): string
    {
        $commentable = $this->comment->commentable;

        if ($commentable === null) {
            return '';
        }

        if (property_exists($commentable, 'title') || isset($commentable->title)) {
            return " on \"{$commentable->title}\"";
        }

        if (property_exists($commentable, 'name') || isset($commentable->name)) {
            return " on \"{$commentable->name}\"";
        }

        return '';
    }
}
