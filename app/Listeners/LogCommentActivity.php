<?php

namespace App\Listeners;

use Illuminate\Support\Str;
use Relaticle\Comments\Events\CommentCreated;

class LogCommentActivity
{
    public function handle(CommentCreated $event): void
    {
        $subjectLabel = $event->commentable->title
            ?? $event->commentable->name
            ?? Str::headline(class_basename($event->commentable)).' #'.$event->commentable->getKey();

        activity('comment')
            ->performedOn($event->commentable)
            ->causedBy($event->comment->commenter)
            ->event('created')
            ->withProperties(['comment_id' => $event->comment->id])
            ->log("commented on \"{$subjectLabel}\"");
    }
}
