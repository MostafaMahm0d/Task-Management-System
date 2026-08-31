<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\TaskStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTaskRequest;
use App\Http\Requests\Api\V1\UpdateTaskRequest;
use App\Http\Requests\Api\V1\UpdateTaskStatusRequest;
use App\Http\Resources\TaskResource;
use App\Models\Status;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Task::class);

        $tasks = Task::query()
            ->visibleTo($request->user())
            ->with(['status', 'assignee', 'reporter', 'labels'])
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
            ->when($request->filled('status_id'), fn ($query) => $query->where('status_id', $request->integer('status_id')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->string('priority')))
            ->when($request->filled('assignee_id'), fn ($query) => $query->where('assignee_id', $request->integer('assignee_id')))
            ->when($request->boolean('unassigned'), fn ($query) => $query->whereNull('assignee_id'))
            ->when($request->filled('reporter_id'), fn ($query) => $query->where('reporter_id', $request->integer('reporter_id')))
            ->when($request->filled('label_id'), fn ($query) => $query->whereHas('labels', fn ($query) => $query->whereKey($request->integer('label_id'))))
            ->when($request->filled('due_after'), fn ($query) => $query->whereDate('due_date', '>=', $request->date('due_after')))
            ->when($request->filled('due_before'), fn ($query) => $query->whereDate('due_date', '<=', $request->date('due_before')))
            ->when($request->boolean('overdue'), fn ($query) => $query
                ->where('due_date', '<', today())
                ->whereHas('status', fn ($query) => $query->where('is_completed', false)->where('is_cancelled', false)))
            ->when($request->boolean('blocked'), fn ($query) => $query
                ->whereHas('dependsOn', fn ($query) => $query
                    ->whereHas('status', fn ($query) => $query->where('is_completed', false)->where('is_cancelled', false))))
            ->when($request->boolean('top_level_only'), fn ($query) => $query->whereNull('parent_task_id'))
            ->when($request->filled('parent_task_id'), fn ($query) => $query->where('parent_task_id', $request->integer('parent_task_id')))
            ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', '%'.$request->string('search').'%'))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return TaskResource::collection($tasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskRequest $request)
    {
        $task = Task::create([
            ...$request->safe()->except('labels'),
            'status_id' => $request->safe()->input('status_id') ?? Status::where('is_default', true)->value('id'),
            'reporter_id' => $request->user()->id,
        ]);

        if ($request->safe()->has('labels')) {
            $task->labels()->sync($request->safe()->input('labels'));
        }

        return TaskResource::make($task->refresh()->load(['status', 'assignee', 'reporter', 'labels']))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Task $task)
    {
        $this->authorize('view', $task);

        return TaskResource::make($task->load(['status', 'assignee', 'reporter', 'labels', 'dependsOn', 'parent', 'subtasks.status']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task)
    {
        $task->update($request->safe()->except('labels'));

        if ($request->safe()->has('labels')) {
            $task->labels()->sync($request->safe()->input('labels'));
        }

        return TaskResource::make($task->load(['status', 'assignee', 'reporter', 'labels']));
    }

    /**
     * Update the status of the specified resource, e.g. from a Kanban board drag-and-drop.
     */
    public function updateStatus(UpdateTaskStatusRequest $request, Task $task)
    {
        $previousStatusId = $task->status_id;

        $task->update($request->validated());

        event(new TaskStatusUpdated($task->refresh(), $previousStatusId));

        return TaskResource::make($task->load(['status', 'assignee', 'reporter', 'labels']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Task $task)
    {
        $this->authorize('delete', $task);

        $task->delete();

        return response()->noContent();
    }
}
