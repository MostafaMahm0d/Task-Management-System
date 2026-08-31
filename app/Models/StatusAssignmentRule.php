<?php

namespace App\Models;

use Database\Factories\StatusAssignmentRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

#[Fillable(['status_id', 'strategy', 'role'])]
class StatusAssignmentRule extends Model
{
    /** @use HasFactory<StatusAssignmentRuleFactory> */
    use HasFactory;

    public const STRATEGY_CREATOR = 'creator';

    public const STRATEGY_OWNER = 'owner';

    public const STRATEGY_ROLE = 'role';

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * Resolve who a task should be reassigned to when it enters this rule's status,
     * or null if the strategy has no eligible candidate (rule left unconfigured, no
     * project member holds the required role, etc).
     */
    public function resolveAssigneeFor(Task $task): ?User
    {
        return match ($this->strategy) {
            self::STRATEGY_CREATOR => $task->reporter,
            self::STRATEGY_OWNER => $task->project->owner,
            self::STRATEGY_ROLE => $this->resolveByRole($task),
            default => null,
        };
    }

    private function resolveByRole(Task $task): ?User
    {
        $candidates = $task->project->members()->wherePivot('role', $this->role)->get();

        if ($candidates->isEmpty()) {
            $this->logSkipped($task, "no project member holds the \"{$this->role}\" role");

            return null;
        }

        // Round-robin via least-loaded: whoever currently carries the fewest open
        // tasks on this project gets the next one, instead of a stateful queue.
        return $candidates->sortBy(fn (User $user) => Task::query()
            ->where('project_id', $task->project_id)
            ->where('assignee_id', $user->id)
            ->whereHas('status', fn ($query) => $query->where('is_completed', false)->where('is_cancelled', false))
            ->count()
        )->first();
    }

    private function logSkipped(Task $task, string $reason): void
    {
        Log::warning("Status assignment rule skipped for task #{$task->id}: {$reason}.", [
            'task_id' => $task->id,
            'status_id' => $this->status_id,
            'rule_id' => $this->id,
        ]);
    }
}
