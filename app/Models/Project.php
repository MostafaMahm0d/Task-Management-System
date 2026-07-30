<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
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

#[Fillable(['name', 'description', 'owner_id'])]
class Project extends Model implements Commentable
{
    /** @use HasFactory<ProjectFactory> */
    use HasComments, HasFactory, LogsActivity;

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->using(ProjectMember::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function isMember(User $user): bool
    {
        return $this->owner_id === $user->id || $this->members()->whereKey($user->id)->exists();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('project.manageAll')) {
            return $query;
        }

        return $query->where(
            fn (Builder $query) => $query
                ->where('owner_id', $user->id)
                ->orWhereHas('members', fn (Builder $query) => $query->whereKey($user->id))
        );
    }

    /**
     * @return array<int>
     */
    public function assignableUserIds(): array
    {
        return [$this->owner_id, ...$this->members()->pluck('users.id')->all()];
    }

    /**
     * Annotates each project with its task/completed/overdue counts, shared by the Task
     * Completion and Project Performance reports so the same aggregate query isn't duplicated.
     */
    public function scopeWithTaskAggregates(Builder $query): Builder
    {
        return $query
            ->withCount('tasks')
            ->withCount(['tasks as completed_tasks_count' => fn (Builder $query) => $query
                ->whereHas('status', fn (Builder $query) => $query->where('is_completed', true))])
            ->withCount(['tasks as overdue_tasks_count' => fn (Builder $query) => $query->overdue()]);
    }

    /**
     * @return array{active_projects: int, avg_completion_rate: float, avg_on_time_rate: float}
     */
    public static function performanceSummary(Builder $query): array
    {
        $projects = (clone $query)->withTaskAggregates()->having('tasks_count', '>', 0)->get();

        if ($projects->isEmpty()) {
            return ['active_projects' => 0, 'avg_completion_rate' => 0.0, 'avg_on_time_rate' => 0.0];
        }

        $completionRates = $projects->map(fn (self $project): float => $project->tasks_count === 0
            ? 0.0
            : $project->completed_tasks_count / $project->tasks_count * 100);

        $onTimeRates = $projects->map(fn (self $project): float => $project->tasks_count === 0
            ? 0.0
            : ($project->tasks_count - $project->overdue_tasks_count) / $project->tasks_count * 100);

        return [
            'active_projects' => $projects->count(),
            'avg_completion_rate' => round((float) $completionRates->avg(), 2),
            'avg_on_time_rate' => round((float) $onTimeRates->avg(), 2),
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('project')
            ->logOnly(['name', 'description', 'owner_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        if ($eventName === 'created') {
            return 'created the project';
        }

        if ($eventName === 'deleted') {
            return 'deleted the project';
        }

        if ($eventName !== 'updated') {
            return $eventName;
        }

        $parts = [];

        if ($this->wasChanged('name')) {
            $parts[] = 'renamed the project';
        }

        if ($this->wasChanged('description')) {
            $parts[] = 'updated the description';
        }

        if ($this->wasChanged('owner_id')) {
            $parts[] = 'transferred ownership of the project';
        }

        return $parts === [] ? 'updated the project' : implode(' and ', $parts);
    }

    /**
     * Activity for this project itself, plus activity on all of its tasks (including comments).
     */
    public function activityLogQuery(): Builder
    {
        $taskIds = $this->tasks()->pluck('id');

        return Activity::query()
            ->where(function (Builder $query) use ($taskIds) {
                $query->where(fn (Builder $q) => $q->where('subject_type', static::class)->where('subject_id', $this->id))
                    ->orWhere(fn (Builder $q) => $q->where('subject_type', Task::class)->whereIn('subject_id', $taskIds));
            });
    }
}
