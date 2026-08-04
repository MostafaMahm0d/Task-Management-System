<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TimeLog;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class TimeLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return true;
    }

    public function view(AuthUser $authUser, TimeLog $timeLog): bool
    {
        return $authUser->can('task.manageAll') || $timeLog->task->project->isMember($authUser);
    }

    public function create(AuthUser $authUser): bool
    {
        return true;
    }

    public function update(AuthUser $authUser, TimeLog $timeLog): bool
    {
        return $authUser->can('task.manageAll') || $timeLog->user_id === $authUser->id;
    }

    public function delete(AuthUser $authUser, TimeLog $timeLog): bool
    {
        return $authUser->can('task.manageAll') || $timeLog->user_id === $authUser->id;
    }
}
