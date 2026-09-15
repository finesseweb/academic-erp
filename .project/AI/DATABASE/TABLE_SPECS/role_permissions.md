# Table Specification: Role Permissions

## Identity
- Physical table / Laravel migrations / Eloquent model: `role_permissions` / `RolePermission`
- Scope/grain: inherits role ownership; one row per role-permission grant

## Keys and Relationships
- Composite PK (`role_id`, `permission_id`) prevents duplicate grants.
- Both required FKs use RESTRICT delete and CASCADE key update.
- Reverse index on `permission_id` supports finding roles granted a permission.
- Includes `created_at` and `updated_at` for configuration timing.

## Access / Security
- Effective permission joins `user_roles -> roles -> role_permissions -> permissions` with active/effective filters and assignment scope.
- Permission changes are transactionally applied and audited by services when implemented.

## Change History
- 2026-08-13: Created by Core Identity/RBAC foundation.
- 2026-08-20: Added reverse permission index and implemented validated, audited role-permission synchronization with protected system roles.
