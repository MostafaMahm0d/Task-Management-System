<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Rating;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RatingPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Rating');
    }

    public function view(AuthUser $authUser, Rating $rating): bool
    {
        if (! $authUser->can('View:Rating')) {
            return false;
        }

        if ($authUser->can('rating.viewAll')) {
            return true;
        }

        // View:Rating without ViewAny:Rating means "can only see ratings I created".
        if (! $authUser->can('ViewAny:Rating')) {
            return $rating->rater_id === $authUser->id;
        }

        return $rating->employee_id === $authUser->id || $rating->rater_id === $authUser->id;
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Rating');
    }

    public function update(AuthUser $authUser, Rating $rating): bool
    {
        return $authUser->can('Update:Rating') && ($authUser->can('rating.manageAll') || $rating->rater_id === $authUser->id);
    }

    public function delete(AuthUser $authUser, Rating $rating): bool
    {
        return $authUser->can('Delete:Rating') && ($authUser->can('rating.manageAll') || $rating->rater_id === $authUser->id);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Rating');
    }

    public function restore(AuthUser $authUser, Rating $rating): bool
    {
        return $authUser->can('Restore:Rating');
    }

    public function forceDelete(AuthUser $authUser, Rating $rating): bool
    {
        return $authUser->can('ForceDelete:Rating');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Rating');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Rating');
    }

    public function replicate(AuthUser $authUser, Rating $rating): bool
    {
        return $authUser->can('Replicate:Rating');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Rating');
    }
}
