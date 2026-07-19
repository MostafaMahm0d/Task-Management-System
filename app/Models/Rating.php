<?php

namespace App\Models;

use Database\Factories\RatingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['employee_id', 'rater_id', 'project_id', 'work_quality', 'communication', 'teamwork', 'punctuality', 'overall_score', 'period_start', 'period_end', 'comments'])]
class Rating extends Model
{
    /** @use HasFactory<RatingFactory> */
    use HasFactory;

    public const METRIC_WORK_QUALITY = 'work_quality';

    public const METRIC_COMMUNICATION = 'communication';

    public const METRIC_TEAMWORK = 'teamwork';

    public const METRIC_PUNCTUALITY = 'punctuality';

    protected function casts(): array
    {
        return [
            'work_quality' => 'integer',
            'communication' => 'integer',
            'teamwork' => 'integer',
            'punctuality' => 'integer',
            'overall_score' => 'decimal:2',
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return array<string, string>
     */
    public static function metrics(): array
    {
        return [
            self::METRIC_WORK_QUALITY => 'Work Quality',
            self::METRIC_COMMUNICATION => 'Communication',
            self::METRIC_TEAMWORK => 'Teamwork',
            self::METRIC_PUNCTUALITY => 'Punctuality',
        ];
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('rating.viewAll')) {
            return $query;
        }

        // View:Rating without ViewAny:Rating means "can only see ratings I created".
        if (! $user->can('ViewAny:Rating')) {
            return $query->where('rater_id', $user->id);
        }

        return $query->where(
            fn (Builder $query) => $query
                ->where('employee_id', $user->id)
                ->orWhere('rater_id', $user->id)
        );
    }

    /**
     * @return array{count: int, average_overall: float, averages: array<string, float>}
     */
    public static function scoreSummaryFor(User $employee): array
    {
        $ratings = static::query()->where('employee_id', $employee->id)->get();

        if ($ratings->isEmpty()) {
            return [
                'count' => 0,
                'average_overall' => 0.0,
                'averages' => collect(self::metrics())->map(fn () => 0.0)->all(),
            ];
        }

        $averages = collect(self::metrics())
            ->map(fn (string $label, string $metric) => round((float) $ratings->avg($metric), 2))
            ->all();

        return [
            'count' => $ratings->count(),
            'average_overall' => round((float) $ratings->avg('overall_score'), 2),
            'averages' => $averages,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Rating $rating) {
            $rating->overall_score = round((
                $rating->work_quality + $rating->communication + $rating->teamwork + $rating->punctuality
            ) / 4, 2);
        });
    }
}
