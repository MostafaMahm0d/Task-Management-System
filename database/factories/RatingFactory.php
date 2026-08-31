<?php

namespace Database\Factories;

use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rating>
 */
class RatingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => User::factory(),
            'rater_id' => User::factory(),
            'project_id' => null,
            'work_quality' => fake()->numberBetween(1, 5),
            'communication' => fake()->numberBetween(1, 5),
            'teamwork' => fake()->numberBetween(1, 5),
            'punctuality' => fake()->numberBetween(1, 5),
            'overall_score' => 0,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'comments' => fake()->sentence(),
        ];
    }
}
