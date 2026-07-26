<?php

namespace Database\Seeders\tenant;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Custom dot-notation permissions per role (things that don't map to a
     * Filament resource/page/widget). This is the single source of truth,
     * also read by RoleObserver: Shield's Role editor form only knows about
     * Pages/Widgets/Resources tabs, so saving a role through its UI replaces
     * the whole permission set and silently drops anything not in those
     * tabs — RoleObserver re-grants this list after every save to compensate.
     *
     * @return array<string, array<int, string>>
     */
    public static function customPermissions(): array
    {
        return [
            'super_admin' => [
                'project.create', 'task.assign', 'task.move', 'report.view', 'activity.viewAll',
                'project.manageAll', 'task.manageAll', 'rating.viewAll', 'rating.manageAll', 'report.export',
            ],
            'tenant_admin' => [
                'project.create', 'task.assign', 'task.move', 'report.view', 'activity.viewAll',
                'project.manageAll', 'task.manageAll', 'rating.viewAll', 'rating.manageAll', 'report.export',
            ],
            'project_manager' => [
                'project.create', 'task.assign', 'task.move', 'report.view',
                'rating.viewAll', 'report.export',
            ],
            'employee' => [
                'task.move', 'report.view',
            ],
        ];
    }

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $customPermissions = static::customPermissions();

        // Dashboard widgets use Shield's HasWidgetShield trait, so every widget needs its
        // own View:{Widget} permission. All three default roles get every widget by default;
        // custom roles created via Shield's Role management UI can opt out per widget.
        $widgetPermissions = [
            'View:ClockWidget',
            'View:DashboardOverview',
            'View:PerformanceOverview',
            'View:TenantRatingOverview',
            'View:RatingTrendChart',
            'View:TaskStatusChart',
            'View:MetricBreakdownChart',
            'View:UpcomingDeadlines',
            'View:RecentActivity',
            'View:MyProjects',
            'View:MyTasksWidget',
            'View:TaskCompletionOverview',
            'View:TaskCompletionTrendChart',
            'View:TeamWorkloadOverview',
            'View:TeamWorkloadChart',
            'View:OverdueTasksOverview',
            'View:OverdueByProjectChart',
            'View:ProjectPerformanceOverview',
            'View:ProjectPerformanceChart',
        ];

        // Reports are a manager/admin concern (team workload / project performance are
        // oversight tools) — employees don't get these 4 pages, matching the ratings
        // viewAll/manageAll tiering convention already used elsewhere in this seeder.
        $reportPagePermissions = [
            'View:TaskCompletionReport',
            'View:TeamWorkloadReport',
            'View:OverdueTasksReport',
            'View:ProjectPerformanceReport',
        ];

        $rolePermissions = [
            'tenant_admin' => [
                ...$customPermissions['tenant_admin'],
                'ViewAny:Project', 'View:Project', 'Create:Project', 'Update:Project', 'Delete:Project', 'DeleteAny:Project',
                'ViewAny:User', 'View:User', 'Create:User', 'Update:User', 'Delete:User', 'DeleteAny:User',
                'ViewAny:Task', 'View:Task', 'Create:Task', 'Update:Task', 'Delete:Task', 'DeleteAny:Task',
                'ViewAny:Label', 'View:Label', 'Create:Label', 'Update:Label', 'Delete:Label', 'DeleteAny:Label',
                'ViewAny:Status', 'View:Status', 'Create:Status', 'Update:Status', 'Delete:Status', 'DeleteAny:Status',
                'View:NotificationSettings',
                'ViewAny:Activity', 'View:Activity',
                'ViewActivity:Task', 'ViewActivity:Project',
                'ViewAny:Rating', 'View:Rating', 'Create:Rating', 'Update:Rating', 'Delete:Rating', 'DeleteAny:Rating',
                'View:Dashboard', 'View:PerformanceDashboard', 'View:TenantPerformanceDashboard',
                ...$reportPagePermissions,
                ...$widgetPermissions,
            ],
            'project_manager' => [
                ...$customPermissions['project_manager'],
                'ViewAny:Project', 'View:Project', 'Create:Project', 'Update:Project',
                'ViewAny:Task', 'View:Task', 'Create:Task', 'Update:Task',
                'ViewAny:Label', 'View:Label', 'Create:Label', 'Update:Label',
                'ViewAny:Status', 'View:Status',
                'ViewAny:Activity', 'View:Activity',
                'ViewActivity:Task', 'ViewActivity:Project',
                'ViewAny:Rating', 'View:Rating', 'Create:Rating', 'Update:Rating',
                'View:Dashboard', 'View:PerformanceDashboard', 'View:TenantPerformanceDashboard',
                ...$reportPagePermissions,
                ...$widgetPermissions,
            ],
            'employee' => [
                ...$customPermissions['employee'],
                'ViewAny:Project', 'View:Project',
                'ViewAny:Task', 'View:Task', 'Update:Task',
                'ViewAny:Label', 'View:Label',
                'ViewAny:Status', 'View:Status',
                'ViewAny:Rating', 'View:Rating',
                'View:Dashboard', 'View:PerformanceDashboard',
                ...$widgetPermissions,
            ],
        ];

        collect($rolePermissions)
            ->flatten()
            ->unique()
            ->each(fn (string $name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));

        foreach ($rolePermissions as $role => $permissionNames) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'])
                ->syncPermissions($permissionNames);
        }
    }
}
