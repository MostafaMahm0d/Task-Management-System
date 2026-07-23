<?php

namespace App\Models;

use App\Enums\TaskPriority;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Relaticle\Comments\Concerns\HasComments;
use Relaticle\Comments\Contracts\Commentable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['project_id', 'parent_task_id', 'title', 'description', 'status_id', 'priority', 'assignee_id', 'reporter_id', 'due_date', 'estimated_hours'])]
class Task extends Model implements Commentable
{
    /** @use HasFactory<TaskFactory> */
    use HasComments, HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'priority' => TaskPriority::class,
            'due_date' => 'date',
            'estimated_hours' => 'decimal:2',
            'overdue_notified_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    public function isSubtask(): bool
    {
        return $this->parent_task_id !== null;
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
            ->logOnly(['title', 'status_id', 'priority', 'assignee_id', 'due_date', 'parent_task_id'])
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
            $parts[] = "changed priority to {$this->priority->getLabel()}";
        }

        if ($this->wasChanged('due_date')) {
            $parts[] = 'changed the due date';
        }

        if ($this->wasChanged('parent_task_id')) {
            $parts[] = $this->parent
                ? "made this a subtask of \"{$this->parent->title}\""
                : 'removed the parent task';
        }

        return $parts === [] ? 'updated the task' : implode(' and ', $parts);
    }
}
