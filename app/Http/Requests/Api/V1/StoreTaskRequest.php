<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Project;
use App\Models\Task;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Task::class);
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
                'required',
                Rule::exists('projects', 'id'),
                function (string $attribute, mixed $value, Closure $fail) {
                    $project = Project::find($value);

                    if ($project && ! $project->isMember($this->user())) {
                        $fail('You are not a member of this project.');
                    }
                },
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in([
                Task::STATUS_TODO, Task::STATUS_IN_PROGRESS, Task::STATUS_IN_REVIEW, Task::STATUS_DONE, Task::STATUS_CANCELLED,
            ])],
            'priority' => ['nullable', Rule::in([
                Task::PRIORITY_LOW, Task::PRIORITY_MEDIUM, Task::PRIORITY_HIGH, Task::PRIORITY_URGENT,
            ])],
            'assignee_id' => [
                'nullable',
                Rule::exists('users', 'id'),
                function (string $attribute, mixed $value, Closure $fail) {
                    $project = Project::find($this->input('project_id'));

                    if ($project && ! in_array((int) $value, $project->assignableUserIds(), true)) {
                        $fail('The assignee must be a member of the selected project.');
                    }
                },
            ],
            'due_date' => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'labels' => ['nullable', 'array'],
            'labels.*' => [Rule::exists('labels', 'id')],
        ];
    }
}
