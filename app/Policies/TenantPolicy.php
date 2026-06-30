<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tenant;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class TenantPolicy
{
    use HandlesAuthorization;



    public function viewAny(AuthUser $user): bool
    {
        return $user->can('ViewAny:Tenant');
    }

    public function view(AuthUser $user, Tenant $tenant): bool
    {
        return $user->can('View:Tenant');
    }

    public function create(AuthUser $user): bool
    {
        return $user->can('Create:Tenant');
    }

    public function update(AuthUser $user, Tenant $tenant): bool
    {
        return $user->can('Update:Tenant');
    }

    public function delete(AuthUser $user, Tenant $tenant): bool
    {
        return $user->can('Delete:Tenant');
    }

    public function deleteAny(AuthUser $user): bool
    {
        return $user->can('DeleteAny:Tenant');
    }

    public function createSuperAdmin(AuthUser $user, Tenant $tenant): bool
    {
        return $user->can('CreateSuperAdmin:Tenant');
    }
}
