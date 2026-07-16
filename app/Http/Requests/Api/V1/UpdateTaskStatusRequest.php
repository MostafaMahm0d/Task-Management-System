<?php

namespace App\Http\Requests\Api\V1;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('task.move')
            && $this->user()->can('update', $this->route('task'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status_id' => [
                'required',
                Rule::exists('statuses', 'id'),
                function (string $attribute, mixed $value, Closure $fail): void {
                    $task = $this->route('task');

                    if (! $task->status?->allowsTransitionTo((int) $value)) {
                        $fail('This status transition is not allowed.');
                    }
                },
            ],
        ];
    }
}
