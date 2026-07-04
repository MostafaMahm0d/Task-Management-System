<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTaskRequest;
use App\Http\Requests\Api\V1\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
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
            ->with(['assignee', 'reporter', 'labels'])
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->string('priority')))
            ->when($request->filled('assignee_id'), fn ($query) => $query->where('assignee_id', $request->integer('assignee_id')))
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
            'reporter_id' => $request->user()->id,
        ]);

        if ($request->safe()->has('labels')) {
            $task->labels()->sync($request->safe()->input('labels'));
        }

        return TaskResource::make($task->refresh()->load(['assignee', 'reporter', 'labels']))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Task $task)
    {
        $this->authorize('view', $task);

        return TaskResource::make($task->load(['assignee', 'reporter', 'labels', 'dependsOn']));
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

        return TaskResource::make($task->load(['assignee', 'reporter', 'labels']));
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
