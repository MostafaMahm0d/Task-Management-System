<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Models\Project;
use App\Models\Status;
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
            'status_id' => fn () => Status::inRandomOrder()->value('id') ?? Status::factory(),
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+2 months'),
            'estimated_hours' => fake()->optional()->randomFloat(2, 1, 40),
        ];
    }

    public function subtaskOf(Task $parent): static
    {
        return $this->state(fn (): array => [
            'project_id' => $parent->project_id,
            'parent_task_id' => $parent->id,
        ]);
    }
}
