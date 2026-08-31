# EPIC 15 — Dynamic Status Workflow Engine

Depends on: EPIC 4 (Task Management Engine), EPIC 5 (Kanban Board)

## 🎯 Goal

Let each tenant define its own rules for how a task can move between
statuses, instead of allowing any status to jump to any other status.

## Stories

- As a tenant admin, I can define which status transitions are allowed
  (e.g. "To Do" → "In Progress" is allowed, "To Do" → "Done" is not)
- As a user, I can only move a task to a status that is a valid transition
  from its current status, on both the Kanban board and the API
- As a tenant admin, I can require conditions before a transition is
  allowed (e.g. task must have an assignee, dependencies must be complete)
- As a system, a status transition can trigger side effects (notify
  assignee, write an activity log entry, unblock dependent tasks)
- As a tenant admin, I can configure a status so that entering it
  automatically (re)assigns the task, choosing per status from:
  the task's creator/reporter, the project's owner, or a project role
  (currently just Manager) — with round-robin among multiple matches when
  more than one user qualifies for a role
  (e.g. entering "In Review" auto-assigns the project's Manager)
- As a user, I get notified when a task is auto-assigned to me by a status
  change, the same way I would from a manual assignment

## Data Model

- `status_transitions` table: `from_status_id`, `to_status_id`,
  tenant-scoped, with an optional `requires` config (assignee present,
  dependencies completed, required role/permission)
- `status_assignment_rules` table: `status_id`, `strategy`
  (`creator` | `owner` | `role`), plus `role` (currently just Manager —
  Owner is handled by the dedicated `owner` strategy via the project's
  `owner_id`, not the `project_members` pivot) when strategy is `role` —
  tenant-scoped, one active rule per status
- When strategy is `role` and multiple project members hold that role,
  resolve deterministically via round-robin (least-recently-assigned
  first) rather than picking arbitrarily
- Tenants with no configured transitions/rules keep today's behavior (any
  status → any status, no auto-assignment), so existing tenants aren't
  broken by this epic

## Tasks

- `StatusTransition` model + migration (tenant DB)
- `StatusAssignmentRule` model + migration (tenant DB)
- Filament UI for tenant admins to build the transition graph (matrix or
  from/to picker) under the existing Statuses resource
- Filament UI for tenant admins to attach an assignment rule to a status
- Enforce transition rules in the Kanban board's move guard and in the
  `PATCH /api/v1/tasks/{task}/status` endpoint
- Apply the matching assignment rule after a successful transition:
  `creator` assigns the task's original reporter, `owner` assigns the
  project's owner (`Project::owner()`), `role` resolves against the task's
  project members holding that role (round-robin if more than one); skip
  with a logged warning if no eligible member exists rather than failing
  the move
- Extend `TaskStatusUpdated` handling so a transition can dispatch
  configured side effects (notification, activity log, auto-assignment)
- Reuse the existing `TaskAssigned`-style notification path so
  auto-assignment notifies the new assignee like a manual assignment does
- Seed sensible default transitions for the five default statuses
- Policy/permission check for who is allowed to perform a given transition
  (separate from who can edit the task at all)

## Acceptance Criteria

- Invalid transitions are rejected on both the Kanban board and the API,
  with a clear validation message
- Tenants can configure their own workflow without a code change
- Tenants with no configured rules see no change in behavior
- Configured side effects fire reliably when a transition succeeds
- A task entering a status with an assignment rule is reassigned
  accordingly and the new assignee is notified
- A status change with no matching assignment rule leaves the current
  assignee untouched
