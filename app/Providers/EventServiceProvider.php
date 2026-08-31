<?php

namespace App\Providers;

use App\Events\TaskAssigned;
use App\Events\TaskCancelled;
use App\Events\TaskCompleted;
use App\Listeners\LogCommentActivity;
use App\Listeners\NotifyDependentTasksOnCancellation;
use App\Listeners\NotifyDependentTasksOnCompletion;
use App\Listeners\SendCommentMentionedNotification;
use App\Listeners\SendTaskAssignedNotification;
use App\Listeners\SendTaskCompletedNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Relaticle\Comments\Events\CommentCreated;
use Relaticle\Comments\Events\UserMentioned;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        TaskAssigned::class => [
            SendTaskAssignedNotification::class,
        ],
        TaskCompleted::class => [
            SendTaskCompletedNotification::class,
            NotifyDependentTasksOnCompletion::class,
        ],
        TaskCancelled::class => [
            NotifyDependentTasksOnCancellation::class,
        ],
        UserMentioned::class => [
            SendCommentMentionedNotification::class,
        ],
        CommentCreated::class => [
            LogCommentActivity::class,
        ],
    ];
}
