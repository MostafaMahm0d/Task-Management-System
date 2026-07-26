<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Relaticle\Comments\Concerns\CanComment;
use Relaticle\Comments\Contracts\Commentator;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements Commentator, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use CanComment, HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_protected' => 'boolean',
        ];
    }

    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_members')
            ->using(ProjectMember::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assignee_id');
    }

    public function reportedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'reporter_id');
    }

    public function ratingsReceived(): HasMany
    {
        return $this->hasMany(Rating::class, 'employee_id');
    }

    public function ratingsGiven(): HasMany
    {
        return $this->hasMany(Rating::class, 'rater_id');
    }

    /**
     * Annotates each user with their open/overdue/urgent assigned-task counts, for the
     * Team Workload report. "Team" here is every user in the tenant, matching the existing
     * Team Ranking convention on TenantPerformanceDashboard.
     */
    public function scopeWithWorkloadAggregates(Builder $query): Builder
    {
        $openTasks = fn (Builder $query) => $query
            ->whereHas('status', fn (Builder $query) => $query->where('is_completed', false)->where('is_cancelled', false));

        return $query
            ->withCount(['assignedTasks as open_tasks_count' => $openTasks])
            ->withCount(['assignedTasks as overdue_tasks_count' => fn (Builder $query) => $query->overdue()])
            ->withCount(['assignedTasks as urgent_tasks_count' => fn (Builder $query) => $openTasks($query)
                ->whereIn('priority', [Task::PRIORITY_URGENT, Task::PRIORITY_HIGH])]);
    }

    /**
     * @return array{total_open_tasks: int, avg_tasks_per_person: float, most_loaded: ?string}
     */
    public static function workloadSummary(Builder $query): array
    {
        $users = (clone $query)->withWorkloadAggregates()->having('open_tasks_count', '>', 0)->get();

        if ($users->isEmpty()) {
            return ['total_open_tasks' => 0, 'avg_tasks_per_person' => 0.0, 'most_loaded' => null];
        }

        $totalOpenTasks = (int) $users->sum('open_tasks_count');
        $mostLoaded = $users->sortByDesc('open_tasks_count')->first();

        return [
            'total_open_tasks' => $totalOpenTasks,
            'avg_tasks_per_person' => round($totalOpenTasks / $users->count(), 2),
            'most_loaded' => $mostLoaded?->name,
        ];
    }

    public static function isViaCentralImpersonation(): bool
    {
        $expiresAt = session('via_central_impersonation_expires_at');

        return $expiresAt && now()->lt($expiresAt);
    }
}
