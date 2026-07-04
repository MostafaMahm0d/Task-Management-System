<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Label;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class LabelPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Label');
    }

    public function view(AuthUser $authUser, Label $label): bool
    {
        return $authUser->can('View:Label');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Label');
    }

    public function update(AuthUser $authUser, Label $label): bool
    {
        return $authUser->can('Update:Label');
    }

    public function delete(AuthUser $authUser, Label $label): bool
    {
        return $authUser->can('Delete:Label');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Label');
    }

    public function restore(AuthUser $authUser, Label $label): bool
    {
        return $authUser->can('Restore:Label');
    }

    public function forceDelete(AuthUser $authUser, Label $label): bool
    {
        return $authUser->can('ForceDelete:Label');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Label');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Label');
    }

    public function replicate(AuthUser $authUser, Label $label): bool
    {
        return $authUser->can('Replicate:Label');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Label');
    }
}
