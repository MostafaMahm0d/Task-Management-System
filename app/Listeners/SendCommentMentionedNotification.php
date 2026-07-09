<?php

namespace App\Listeners;

use App\Models\User;
use App\Notifications\CommentMentionedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Relaticle\Comments\Events\UserMentioned;

class SendCommentMentionedNotification implements ShouldQueue
{
    public function handle(UserMentioned $event): void
    {
        if (! $event->mentionedUser instanceof User) {
            return;
        }

        if ($event->mentionedUser->id === $event->comment->commenter_id) {
            return;
        }

        $event->mentionedUser->notify(new CommentMentionedNotification($event->comment));
    }
}
