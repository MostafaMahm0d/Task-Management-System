# EPIC 2 — Authentication & RBAC System

## 🎯 Goal

Secure access control system for SaaS users.

## Stories

- Users can register/login per tenant
- Users can reset passwords
- Admin can assign roles & permissions
- System enforces granular permissions

## Roles

- SUPER_ADMIN
- TENANT_ADMIN
- PROJECT_MANAGER
- EMPLOYEE

## Permissions Examples

- project.create
- task.assign
- task.move
- report.view

## Tasks

- Laravel Sanctum authentication
- Role & Permission (Spatie)
- Filament user management
- Login guard per tenant
- Password reset & email verification

## Acceptance Criteria

- Users only see allowed resources
- Permissions enforced at backend + Filament level
