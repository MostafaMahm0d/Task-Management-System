<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Task $task,
        public int $previousStatusId,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel("projects.{$this->task->project_id}.tasks")];
    }

    public function broadcastWith(): array
    {
        return [
            'task_id' => $this->task->id,
            'project_id' => $this->task->project_id,
            'status_id' => $this->task->status_id,
            'previous_status_id' => $this->previousStatusId,
        ];
    }
}
