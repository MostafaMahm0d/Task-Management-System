<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TaskPriority;
use App\Models\Project;
use App\Models\Task;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('task'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'project_id' => [
                'sometimes',
                Rule::exists('projects', 'id'),
                function (string $attribute, mixed $value, Closure $fail) {
                    $project = Project::find($value);

                    if ($project && ! $project->isMember($this->user())) {
                        $fail('You are not a member of this project.');
                    }
                },
            ],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status_id' => ['sometimes', Rule::exists('statuses', 'id')],
            'priority' => ['sometimes', Rule::enum(TaskPriority::class)],
            'assignee_id' => [
                'nullable',
                Rule::exists('users', 'id'),
                function (string $attribute, mixed $value, Closure $fail) {
                    $project = Project::find($this->input('project_id', $this->route('task')->project_id));

                    if ($project && ! in_array((int) $value, $project->assignableUserIds(), true)) {
                        $fail('The assignee must be a member of the selected project.');
                    }
                },
            ],
            'due_date' => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'labels' => ['nullable', 'array'],
            'labels.*' => [Rule::exists('labels', 'id')],
            'parent_task_id' => [
                'nullable',
                Rule::exists('tasks', 'id'),
                function (string $attribute, mixed $value, Closure $fail) {
                    /** @var Task $task */
                    $task = $this->route('task');

                    if ($value && (int) $value === $task->id) {
                        $fail('A task cannot be a subtask of itself.');

                        return;
                    }

                    if ($value && $task->subtasks()->exists()) {
                        $fail('A task with subtasks cannot become a subtask itself.');

                        return;
                    }

                    $parent = $value ? Task::find($value) : null;

                    if (! $parent) {
                        return;
                    }

                    if ((int) $parent->project_id !== (int) $this->input('project_id', $task->project_id)) {
                        $fail('The parent task must belong to the same project.');
                    }

                    if ($parent->isSubtask()) {
                        $fail('A subtask cannot be nested under another subtask.');
                    }
                },
            ],
        ];
    }
}
