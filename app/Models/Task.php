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
        if ($user->hasRole('tenant_admin')) {
            return $query;
        }

        return $query->whereHas(
            'project',
            fn (Builder $query) => $query
                ->where('owner_id', $user->id)
                ->orWhereHas('members', fn (Builder $query) => $query->whereKey($user->id))
        );
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
