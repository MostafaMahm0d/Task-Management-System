# EPIC 5 — Kanban Board (Drag & Drop System)

Status: implemented, pending final browser verification.

## Goal

Give each tenant a visual, drag-and-drop task board (Jira/ClickUp-style), with
statuses that are configurable per tenant instead of hardcoded.

## What changed and why

### 1. Statuses became a real, tenant-configurable model

Previously `tasks.status` was a plain string column with a fixed set of
constants on the `Task` model (`STATUS_TODO`, `STATUS_IN_PROGRESS`, …). That
can't support "customize statuses per tenant," so it was replaced with a
proper `Status` model.

- **Migration** `database/migrations/tenant/2026_07_05_224715_create_statuses_table.php`
  creates the `statuses` table (`name`, `color`, `position`, `is_default`,
  `is_completed`) and seeds five defaults directly in the migration (To Do,
  In Progress, In Review, Done, Cancelled) so every tenant — new or
  already-migrated — gets a working board out of the box.
- **Migration** `database/migrations/tenant/2026_07_05_224716_add_status_id_to_tasks_table.php`
  adds `tasks.status_id` (FK, `restrictOnDelete`), backfills it from the old
  string column (`todo` → "To Do", etc., falling back to "To Do" for
  anything unmapped), then drops the old `status` string column.
- **`App\Models\Status`** (`app/Models/Status.php`) — `tasks()` relation, and
  a `booted()` hook that un-sets `is_default` on every other status when one
  is saved as default, so there's always exactly one default.
- **`App\Models\Task`** — dropped the `STATUS_*` constants, added a
  `status()` belongsTo relation, and `isBlocked()` now checks
  `dependsOn()->whereHas('status', fn ($q) => $q->where('is_completed', false))`
  instead of comparing a hardcoded string — a dependency only unblocks once
  its status is flagged `is_completed`.

`is_completed` is the mechanism that lets a tenant rename/reorder/add
statuses freely without breaking the dependency-blocking logic, since it's
no longer tied to a specific status name.

### 2. Tenants can manage their own statuses in Filament

New resource at `app/Filament/Tenant/Resources/Statuses/`:

- `StatusResource.php`, `Schemas/StatusForm.php` (name, color picker, "is
  default" / "is completed" toggles), `Tables/StatusesTable.php` (drag
  reorder via `->reorderable('position')`, color swatch, task counts),
  `Pages/{ListStatuses,CreateStatus,EditStatus}.php`.
- `app/Policies/StatusPolicy.php` — same shape as the existing
  `LabelPolicy`, permission-driven (`ViewAny:Status`, `Create:Status`, etc).
- Permissions wired into `database/seeders/tenant/RolesAndPermissionsSeeder.php`:
  `tenant_admin` gets full CRUD on statuses; `project_manager` and
  `employee` get view-only (they use the board, they don't redesign it).

### 3. Kanban board UI

Installed `wezlo/filament-kanban` (Filament v5 plugin) and registered it in
`app/Providers/Filament/TenantPanelProvider.php`.

- New page `app/Filament/Tenant/Resources/Tasks/Pages/Board.php`, registered
  as the `board` route on `TaskResource` (`/app/tasks/board`). Uses
  `relationshipColumn('status', 'name', Status::class, orderAttribute:
  'position', colorAttribute: 'color')` — columns are literally the
  tenant's `Status` rows, in their configured order/color.
- Cards show title, assignee, and a priority badge.
- Moving a card between columns is guarded twice: the plugin's own
  `Resource::canEdit()` check (→ `TaskPolicy::update`, i.e. project
  membership), plus an explicit `canMove()` closure that requires the
  `task.move` permission — a permission that already existed in the
  seeder from EPIC 4 but wasn't wired to anything until now.
- `ListTasks` (table view) and `Board` (kanban view) now link to each other
  via a header action, so both views stay reachable.

### 4. API: drag-and-drop endpoint + prep for real-time

- `PATCH /api/v1/tasks/{task}/status` (`TaskController::updateStatus`,
  guarded by new `UpdateTaskStatusRequest`) — lets non-Filament clients
  (mobile, external board UI) move a task between statuses the same way
  the Kanban board does.
- `App\Events\TaskStatusUpdated` (`app/Events/TaskStatusUpdated.php`)
  implements `ShouldBroadcast` on a `projects.{project_id}.tasks` channel.
  It's fired from both the Kanban board's `onRecordMoved` callback and the
  new API endpoint. Broadcasting is on the `log` driver
  (`BROADCAST_CONNECTION=log` in `.env`) and queued via the `database`
  queue connection — nothing is actually pushed over a socket yet, but the
  event/channel shape is in place so swapping in Reverb/Pusher later is a
  config change, not a code change. This satisfies "prep for WebSockets"
  without standing up broadcasting infra this epic didn't ask for.
- `GET /api/v1/tasks` now filters by `status_id` instead of the old
  `status` string, and `TaskResource` (API) returns `status` as
  `{id, name, color}`.

### 5. Everywhere else `status` was referenced

Updated to use the relation instead of the old string/constants:
`TaskForm`, `TasksTable`, `TaskInfolist`, `TasksRelationManager` (under
Projects), `DependsOnRelationManager`, `TaskFactory`, `StoreTaskRequest`,
`UpdateTaskRequest`.

## Files touched (new + modified)

```
app/Models/Status.php                                          (new)
app/Models/Task.php
app/Events/TaskStatusUpdated.php                                (new)
app/Policies/StatusPolicy.php                                   (new)
app/Http/Controllers/Api/V1/TaskController.php
app/Http/Requests/Api/V1/StoreTaskRequest.php
app/Http/Requests/Api/V1/UpdateTaskRequest.php
app/Http/Requests/Api/V1/UpdateTaskStatusRequest.php             (new)
app/Http/Resources/TaskResource.php
app/Filament/Tenant/Resources/Statuses/**                       (new)
app/Filament/Tenant/Resources/Tasks/Pages/Board.php              (new)
app/Filament/Tenant/Resources/Tasks/Pages/ListTasks.php
app/Filament/Tenant/Resources/Tasks/TaskResource.php
app/Filament/Tenant/Resources/Tasks/Schemas/TaskForm.php
app/Filament/Tenant/Resources/Tasks/Schemas/TaskInfolist.php
app/Filament/Tenant/Resources/Tasks/Tables/TasksTable.php
app/Filament/Tenant/Resources/Tasks/RelationManagers/DependsOnRelationManager.php
app/Filament/Tenant/Resources/Projects/RelationManagers/TasksRelationManager.php
app/Providers/Filament/TenantPanelProvider.php
database/migrations/tenant/2026_07_05_224715_create_statuses_table.php        (new)
database/migrations/tenant/2026_07_05_224716_add_status_id_to_tasks_table.php (new)
database/factories/StatusFactory.php                             (new)
database/factories/TaskFactory.php
database/seeders/tenant/RolesAndPermissionsSeeder.php
routes/api.php
composer.json / composer.lock                                    (added wezlo/filament-kanban)
config/filament-kanban.php                                       (new, published)
```

## Environment work done alongside the code

- Docker Compose wasn't running; port 5173 conflicted with an unrelated
  container (`gym_erp_app`) on this machine, so the app's Vite port mapping
  was remapped to `5174:5173` in `docker-compose.yml` and containers were
  started.
- `vendor/laravel/sanctum` was missing (added to `composer.json` in the
  EPIC 4 commit but never installed). The `app` container can't reach
  GitHub's codeload servers (network-restricted sandbox), so
  `composer install` / `composer require wezlo/filament-kanban` were run
  from the host (which has network access) against the same bind-mounted
  `vendor/` — same effect as running them in the container.
- Ran `php artisan tenants:migrate` to bring the existing `acme` test
  tenant's database up to date (it was missing the tasks/labels/status
  migrations entirely) and re-ran `TenantsDatabaseSeeder` to pick up the
  new `Status` permissions.

## What's verified so far

- `composer.json`/migrations apply cleanly on the `acme` tenant
  (`php artisan tenants:migrate`), including the string→FK backfill.
- Seeded statuses confirmed in the tenant DB (To Do/In Progress/In
  Review/Done/Cancelled, correct `position`/`is_default`/`is_completed`).
- `php artisan test --compact` baseline unchanged (1 pre-existing,
  unrelated failure on `GET /`; nothing regressed by this epic).
- API login (`POST /api/v1/login`) confirmed working against `acme`.

## Not yet done / open items

- **No automated tests were added.** The repo has no tenant-aware test
  harness set up yet (no `RefreshDatabase`, no tenant factory helpers) —
  building that is bigger than this epic's ask, so it was flagged rather
  than done silently. Worth a follow-up before EPIC 6.
- Full browser walkthrough of the Kanban board (drag a card, confirm the
  move persists and the notification/authorization guard behaves) was
  in progress and not yet completed in this session.
- The `GET /api/v1/tasks/{task}/status` drag-and-drop endpoint was not
  yet exercised with a live `curl` request in this session (only the
  login step was verified before verification was paused).
