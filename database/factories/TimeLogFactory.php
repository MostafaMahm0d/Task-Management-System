<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeLog>
 */
class TimeLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'hours' => fake()->randomFloat(2, 0.25, 8),
            'note' => fake()->optional()->sentence(),
            'logged_on' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
