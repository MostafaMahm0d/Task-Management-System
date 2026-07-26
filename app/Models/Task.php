<?php

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Relaticle\Comments\Concerns\HasComments;
use Relaticle\Comments\Contracts\Commentable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['project_id', 'title', 'description', 'status_id', 'priority', 'assignee_id', 'reporter_id', 'due_date', 'estimated_hours'])]
class Task extends Model implements Commentable
{
    /** @use HasFactory<TaskFactory> */
    use HasComments, HasFactory, LogsActivity;

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'estimated_hours' => 'decimal:2',
            'overdue_notified_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class);
    }

    public function dependsOn(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'task_id', 'depends_on_task_id')
            ->withTimestamps();
    }

    public function blocks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'depends_on_task_id', 'task_id')
            ->withTimestamps();
    }

    public function isBlocked(): bool
    {
        return $this->dependsOn()
            ->whereHas('status', fn (Builder $query) => $query->where('is_completed', false)->where('is_cancelled', false))
            ->exists();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('task.manageAll')) {
            return $query;
        }

        return $query->whereHas(
            'project',
            fn (Builder $query) => $query
                ->where('owner_id', $user->id)
                ->orWhereHas('members', fn (Builder $query) => $query->whereKey($user->id))
        );
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->whereDate('due_date', '<', now())
            ->whereHas('status', fn (Builder $query) => $query->where('is_completed', false)->where('is_cancelled', false));
    }

    /**
     * @return array{total: int, completed: int, rate: float, avg_days_to_close: ?float}
     */
    public static function completionSummary(Builder $query): array
    {
        $total = (clone $query)->count();

        // No dedicated "completed_at" column exists — updated_at is used as an approximation
        // of when a task last changed (including its final move to a completed status).
        $completedQuery = (clone $query)->whereHas('status', fn (Builder $query) => $query->where('is_completed', true));
        $completed = $completedQuery->count();

        $avgDaysToClose = $total === 0 ? null : (clone $completedQuery)
            ->selectRaw('AVG(DATEDIFF(updated_at, created_at)) as average')
            ->value('average');

        return [
            'total' => $total,
            'completed' => $completed,
            'rate' => $total === 0 ? 0.0 : round($completed / $total * 100, 2),
            'avg_days_to_close' => $avgDaysToClose === null ? null : round((float) $avgDaysToClose, 1),
        ];
    }

    /**
     * Monthly completion rate trend for the last $months months, cohorted by due_date so
     * every task is counted in a single, stable bucket regardless of when it was closed.
     *
     * @return array<string, float> month ('Y-m') => completion rate percentage
     */
    public static function completionTrend(Builder $query, int $months = 6): array
    {
        $since = now()->subMonths($months - 1)->startOfMonth();

        $rows = (clone $query)
            ->whereNotNull('due_date')
            ->where('due_date', '>=', $since)
            ->selectRaw("DATE_FORMAT(due_date, '%Y-%m') as month, COUNT(*) as total")
            ->selectRaw('SUM(CASE WHEN statuses.is_completed THEN 1 ELSE 0 END) as completed')
            ->join('statuses', 'statuses.id', '=', 'tasks.status_id')
            ->groupBy('month')
            ->get();

        return $rows->mapWithKeys(fn ($row): array => [
            $row->month => $row->total === 0 ? 0.0 : round($row->completed / $row->total * 100, 2),
        ])->all();
    }

    /**
     * @return array{total: int, avg_days_overdue: ?float, by_priority: array<string, int>}
     */
    public static function overdueBreakdown(Builder $query): array
    {
        $overdueQuery = (clone $query)->overdue();

        $total = (clone $overdueQuery)->count();

        $avgDaysOverdue = $total === 0 ? null : (clone $overdueQuery)
            ->selectRaw('AVG(DATEDIFF(?, due_date)) as average', [now()->toDateString()])
            ->value('average');

        $byPriority = (clone $overdueQuery)
            ->selectRaw('priority, COUNT(*) as aggregate')
            ->groupBy('priority')
            ->pluck('aggregate', 'priority')
            ->all();

        return [
            'total' => $total,
            'avg_days_overdue' => $avgDaysOverdue === null ? null : round((float) $avgDaysOverdue, 1),
            'by_priority' => $byPriority,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('task')
            ->logOnly(['title', 'status_id', 'priority', 'assignee_id', 'due_date'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        if ($eventName === 'created') {
            return 'created the task';
        }

        if ($eventName === 'deleted') {
            return 'deleted the task';
        }

        if ($eventName !== 'updated') {
            return $eventName;
        }

        $parts = [];

        if ($this->wasChanged('status_id')) {
            $from = Status::find($this->getOriginal('status_id'))?->name ?? 'none';
            $to = $this->status?->name ?? 'none';
            $parts[] = "moved the task from \"{$from}\" to \"{$to}\"";
        }

        if ($this->wasChanged('assignee_id')) {
            $assignee = $this->assignee?->name ?? 'nobody';
            $parts[] = "assigned the task to {$assignee}";
        }

        if ($this->wasChanged('title')) {
            $parts[] = 'renamed the task';
        }

        if ($this->wasChanged('priority')) {
            $parts[] = "changed priority to {$this->priority}";
        }

        if ($this->wasChanged('due_date')) {
            $parts[] = 'changed the due date';
        }

        return $parts === [] ? 'updated the task' : implode(' and ', $parts);
    }
}
