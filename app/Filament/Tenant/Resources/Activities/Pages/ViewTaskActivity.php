<?php

namespace App\Filament\Tenant\Resources\Activities\Pages;

use App\Filament\Tenant\Resources\Activities\ActivityResource;
use App\Models\Task;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ViewTaskActivity extends ViewRecord
{
    protected static string $resource = ActivityResource::class;

    protected string $view = 'filament.tenant.pages.view-project-activity';

    public function getTitle(): string
    {
        return "Activity — {$this->getRecord()->title}";
    }

    protected function resolveRecord(int|string $key): Model
    {
        $task = Task::query()->find($key);

        if ($task === null) {
            throw (new ModelNotFoundException)->setModel(Task::class, [$key]);
        }

        abort_unless(auth()->user()?->can('viewActivity', $task), 403);

        return $task;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
