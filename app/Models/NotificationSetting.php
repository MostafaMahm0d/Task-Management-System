<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['event', 'channel', 'enabled'])]
class NotificationSetting extends Model
{
    public const EVENT_TASK_ASSIGNED = 'task_assigned';

    public const EVENT_TASK_COMPLETED = 'task_completed';

    public const EVENT_TASK_OVERDUE = 'task_overdue';

    public const EVENT_COMMENT_MENTIONED = 'comment_mentioned';

    public const CHANNEL_DATABASE = 'database';

    public const CHANNEL_MAIL = 'mail';

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function events(): array
    {
        return [
            self::EVENT_TASK_ASSIGNED => 'Task Assigned',
            self::EVENT_TASK_COMPLETED => 'Task Completed',
            self::EVENT_TASK_OVERDUE => 'Task Overdue',
            self::EVENT_COMMENT_MENTIONED => 'Comment Mentioned',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function channels(): array
    {
        return [
            self::CHANNEL_DATABASE => 'In-app',
            self::CHANNEL_MAIL => 'Email',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function enabledChannelsFor(string $event): array
    {
        return static::query()
            ->where('event', $event)
            ->where('enabled', true)
            ->pluck('channel')
            ->all();
    }
}
