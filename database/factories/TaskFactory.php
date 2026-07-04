<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement([
                Task::STATUS_TODO, Task::STATUS_IN_PROGRESS, Task::STATUS_IN_REVIEW, Task::STATUS_DONE,
            ]),
            'priority' => fake()->randomElement([
                Task::PRIORITY_LOW, Task::PRIORITY_MEDIUM, Task::PRIORITY_HIGH, Task::PRIORITY_URGENT,
            ]),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+2 months'),
            'estimated_hours' => fake()->optional()->randomFloat(2, 1, 40),
        ];
    }
}
