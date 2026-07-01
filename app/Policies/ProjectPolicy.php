<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ProjectPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Project');
    }

    public function view(AuthUser $authUser, Project $project): bool
    {
        if (! $authUser->can('View:Project')) {
            return false;
        }

        return $authUser->hasRole('tenant_admin') || $project->isMember($authUser);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Project');
    }

    public function update(AuthUser $authUser, Project $project): bool
    {
        if (! $authUser->can('Update:Project')) {
            return false;
        }

        if ($authUser->hasRole('tenant_admin') || $project->owner_id === $authUser->id) {
            return true;
        }

        $role = $project->members()->whereKey($authUser->id)->value('project_members.role');

        return in_array($role, [ProjectMember::ROLE_OWNER, ProjectMember::ROLE_MANAGER], true);
    }

    public function delete(AuthUser $authUser, Project $project): bool
    {
        if (! $authUser->can('Delete:Project')) {
            return false;
        }

        return $authUser->hasRole('tenant_admin') || $project->owner_id === $authUser->id;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Project');
    }

    public function restore(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('Restore:Project');
    }

    public function forceDelete(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('ForceDelete:Project');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Project');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Project');
    }

    public function replicate(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('Replicate:Project');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Project');
    }
}
