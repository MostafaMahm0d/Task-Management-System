# EPIC 1 — SaaS Foundation & Multi-Tenancy

## 🎯 Goal

Establish core SaaS architecture with strict tenant isolation.

## Stories

- As a system, I can create tenants (organizations)
- As a tenant, I can access isolated data only
- As a user, I can register and join a tenant
- As an admin, I can manage all tenants

## Tasks

- Setup Laravel 11 project
- Setup Filament Admin Panel (SUPER_ADMIN)
- Install stancl/tenancy
- Implement tenant resolution (domain/subdomain/header)
- Create tenant provisioning service
- Add tenant_id global scope enforcement
- Setup tenant-aware middleware

## Acceptance Criteria

- No cross-tenant data access possible
- Each tenant has isolated dataset
- Tenant creation auto-provisions environment
