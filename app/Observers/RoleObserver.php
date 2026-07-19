<?php

namespace App\Observers;

use Database\Seeders\tenant\RolesAndPermissionsSeeder;
use Spatie\Permission\Events\PermissionAttachedEvent;
use Spatie\Permission\Models\Role;

/**
 * Shield's Role editor form only exposes Pages/Widgets/Resources permissions,
 * so saving a role through its UI replaces the whole permission set via
 * syncPermissions() and silently drops any custom dot-notation permission
 * (rating.viewAll, task.manageAll, etc). syncPermissions() ends with an
 * internal givePermissionTo() call, so listening for the event it fires lets
 * us re-grant whatever's missing immediately after — the missing-permission
 * check makes the re-grant a no-op on its own second firing, so this can't loop.
 *
 * Deliberately additive, not a full resync: every other permission a role
 * holds (Resources/Pages/Widgets, including super_admin's) stays exactly as
 * saved — only this fixed custom-permission list is protected from being
 * silently dropped.
 */
class RoleObserver
{
    public function handle(PermissionAttachedEvent $event): void
    {
        if (! $event->model instanceof Role) {
            return;
        }

        $role = $event->model;
        $customPermissions = RolesAndPermissionsSeeder::customPermissions()[$role->name] ?? null;

        if ($customPermissions === null) {
            return;
        }

        $missing = collect($customPermissions)->reject(fn (string $permission) => $role->hasPermissionTo($permission))->values()->all();

        if ($missing === []) {
            return;
        }

        $role->givePermissionTo($missing);
    }
}
