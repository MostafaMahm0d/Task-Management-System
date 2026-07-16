<?php

namespace App\Models;

use Database\Factories\StatusFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'color', 'position', 'is_default', 'is_completed', 'is_cancelled'])]
class Status extends Model
{
    /** @use HasFactory<StatusFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_completed' => 'boolean',
            'is_cancelled' => 'boolean',
        ];
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function transitionsTo(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'status_transitions', 'from_status_id', 'to_status_id')
            ->withTimestamps();
    }

    public function assignmentRule(): HasOne
    {
        return $this->hasOne(StatusAssignmentRule::class);
    }

    /**
     * A status with no configured outgoing transitions allows moving to any status.
     */
    public function allowsTransitionTo(int $toStatusId): bool
    {
        if ($this->id === $toStatusId) {
            return true;
        }

        if (! $this->transitionsTo()->exists()) {
            return true;
        }

        return $this->transitionsTo()->whereKey($toStatusId)->exists();
    }

    protected static function booted(): void
    {
        static::saved(function (Status $status) {
            if ($status->is_default) {
                static::where('id', '!=', $status->id)->update(['is_default' => false]);
            }
        });
    }
}
