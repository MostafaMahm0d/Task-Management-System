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
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['name', 'description', 'owner_id'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, LogsActivity;

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

    /**
     * @return array<int>
     */
    public function assignableUserIds(): array
    {
        return [$this->owner_id, ...$this->members()->pluck('users.id')->all()];
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
