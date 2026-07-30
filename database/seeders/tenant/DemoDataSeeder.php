<?php

namespace Database\Seeders\tenant;

use App\Enums\TaskPriority;
use App\Models\Label;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed a realistic multi-project scenario: several users per role,
     * projects with mixed membership, and tasks spread across every
     * status/priority combination (overdue, blocked, unassigned, etc).
     */
    public function run(): void
    {
        $statuses = Status::query()->orderBy('position')->get();

        $todoStatus = $statuses->firstWhere('name', 'To Do') ?? $statuses->first();
        $inProgressStatus = $statuses->firstWhere('name', 'In Progress') ?? $statuses->get(1);
        $inReviewStatus = $statuses->firstWhere('name', 'In Review') ?? $statuses->get(2);
        $doneStatus = $statuses->firstWhere('is_completed', true) ?? $statuses->last();
        $cancelledStatus = $statuses->firstWhere('name', 'Cancelled') ?? $statuses->last();

        $password = Hash::make('password');

        User::factory()->count(2)->create(['password' => $password])
            ->each(fn (User $user) => $user->assignRole('tenant_admin'));

        $managers = User::factory()->count(3)->create(['password' => $password])
            ->each(fn (User $user) => $user->assignRole('project_manager'));

        $employees = User::factory()->count(10)->create(['password' => $password])
            ->each(fn (User $user) => $user->assignRole('employee'));

        $labels = collect([
            ['name' => 'Bug', 'color' => '#ef4444'],
            ['name' => 'Feature', 'color' => '#3b82f6'],
            ['name' => 'Enhancement', 'color' => '#8b5cf6'],
            ['name' => 'Documentation', 'color' => '#6b7280'],
            ['name' => 'Tech Debt', 'color' => '#f59e0b'],
            ['name' => 'Urgent', 'color' => '#dc2626'],
        ])->map(fn (array $attributes) => Label::firstOrCreate(['name' => $attributes['name']], $attributes));

        $projectBlueprints = [
            ['name' => 'Website Redesign', 'description' => 'Revamp the marketing site and onboarding flow.'],
            ['name' => 'Mobile App Launch', 'description' => 'Ship the first public version of the mobile app.'],
            ['name' => 'Internal Tooling', 'description' => 'Build internal dashboards and automation tools.'],
            ['name' => 'API Platform V2', 'description' => 'Rework the public API for versioning and rate limits.'],
        ];

        foreach ($projectBlueprints as $index => $blueprint) {
            $owner = $managers[$index % $managers->count()];
            $projectManagerMember = $managers[($index + 1) % $managers->count()];
            $projectEmployees = $employees->random(4);

            $project = Project::create([
                'owner_id' => $owner->id,
                'name' => $blueprint['name'],
                'description' => $blueprint['description'],
            ]);

            $project->members()->attach($projectManagerMember->id, ['role' => ProjectMember::ROLE_MANAGER]);

            foreach ($projectEmployees as $employee) {
                $project->members()->attach($employee->id, ['role' => ProjectMember::ROLE_MEMBER]);
            }

            $assignableUsers = collect([$owner, $projectManagerMember, ...$projectEmployees]);

            $tasks = collect();

            // A clear backlog item, unassigned, no due date.
            $tasks->push(Task::create([
                'project_id' => $project->id,
                'title' => "Define scope for {$blueprint['name']}",
                'description' => 'Gather requirements and confirm acceptance criteria with stakeholders.',
                'status_id' => $todoStatus->id,
                'priority' => TaskPriority::Low,
                'assignee_id' => null,
                'reporter_id' => $owner->id,
                'due_date' => null,
                'estimated_hours' => null,
            ]));

            // Overdue, urgent, assigned — the "on fire" task.
            $tasks->push(Task::create([
                'project_id' => $project->id,
                'title' => 'Fix critical regression reported by QA',
                'description' => 'Regression blocks the release candidate; needs a same-day fix.',
                'status_id' => $inProgressStatus->id,
                'priority' => TaskPriority::Urgent,
                'assignee_id' => $assignableUsers->random()->id,
                'reporter_id' => $owner->id,
                'due_date' => now()->subDays(3),
                'estimated_hours' => 6,
            ]));

            // In review, medium priority, due soon.
            $tasks->push(Task::create([
                'project_id' => $project->id,
                'title' => 'Polish UI for the new settings page',
                'description' => 'Address feedback from design review.',
                'status_id' => $inReviewStatus->id,
                'priority' => TaskPriority::Medium,
                'assignee_id' => $assignableUsers->random()->id,
                'reporter_id' => $projectManagerMember->id,
                'due_date' => now()->addDays(2),
                'estimated_hours' => 4.5,
            ]));

            // Done, completed on time.
            $tasks->push(Task::create([
                'project_id' => $project->id,
                'title' => 'Set up CI pipeline',
                'description' => 'Automated tests and linting on every pull request.',
                'status_id' => $doneStatus->id,
                'priority' => TaskPriority::High,
                'assignee_id' => $assignableUsers->random()->id,
                'reporter_id' => $owner->id,
                'due_date' => now()->subDays(10),
                'estimated_hours' => 8,
            ]));

            // Cancelled task.
            $tasks->push(Task::create([
                'project_id' => $project->id,
                'title' => 'Evaluate third-party analytics vendor',
                'description' => 'Deprioritized after budget review.',
                'status_id' => $cancelledStatus->id,
                'priority' => TaskPriority::Low,
                'assignee_id' => null,
                'reporter_id' => $owner->id,
                'due_date' => null,
                'estimated_hours' => null,
            ]));

            // A batch of factory-generated tasks covering random combinations
            // of status/priority/assignment. project_id is passed as an
            // override so the factory's nested Project::factory() default
            // never resolves (it would otherwise persist a phantom project).
            $tasks = $tasks->merge(
                Task::factory()
                    ->count(10)
                    ->make(['project_id' => $project->id])
                    ->each(function (Task $task) use ($assignableUsers, $statuses) {
                        $task->status_id = $statuses->random()->id;
                        $task->assignee_id = fake()->boolean(80) ? $assignableUsers->random()->id : null;
                        $task->reporter_id = $assignableUsers->random()->id;
                        $task->save();
                    })
            );

            // Attach labels: some tasks get none, some get several.
            foreach ($tasks as $task) {
                if (fake()->boolean(70)) {
                    $task->labels()->attach($labels->random(random_int(1, 2))->pluck('id'));
                }
            }

            // Wire up a dependency chain so the board shows a blocked task:
            // the review task depends on the still-in-progress bugfix, and
            // a random later task depends on the backlog item.
            $tasks[2]->dependsOn()->attach($tasks[1]->id);
            $tasks->slice(3)->random()->dependsOn()->attach($tasks[0]->id);
        }
    }
}
