# Table Specification: Roles

## Identity
- Physical table / Laravel migrations / Eloquent model: `roles` / `Role`
- Domain: Authorization
- Scope/grain: one row per reusable permission bundle; globally or Affiliated-College-owned

## Keys and Columns
- PK: unsigned BIGINT `id`; globally unique stable `code`.
- `is_system_role` protects system templates such as `SUPER_ADMIN`; custom roles use the same table.
- `owner_scope_type` + `owner_scope_reference` identifies role ownership, not assignment scope.
- Nullable `created_by_user_id` and immutable `created_by_scope_type` identify whether University or College administration created the role; creation origin is separate from canonical owner scope.
- Status and `created_at`/`updated_at` support non-destructive lifecycle management.

## Relationships and Indexes
- Parent of `role_permissions` and `user_roles`, both RESTRICT on delete. `roles.created_by_user_id` optionally references the creating `users.id` and uses SET NULL on creator deletion.
- `(owner_scope_type, owner_scope_reference, status)` supports scoped role lists.
- `(created_by_scope_type, owner_scope_type, owner_scope_reference, status)` supports creator-hierarchy visibility in University Role Management.

## Rules
- System roles are not deletable or unsafe to rename through normal workflows.
- STUDENT and PARENT fit as future role records; no role-specific user columns belong here.

## Change History
- 2026-08-20: Added protected `COLLEGE_ADMIN` template and College-owned custom-role creation.
- 2026-08-13: Created; seeded protected global `SUPER_ADMIN`.
- 2026-08-18: Repository migration now persists the protected global `SUPER_ADMIN` and grants the two implemented University Profile permissions. Other catalog grants remain milestone-gated.
- 2026-08-19: Added the owner-scope/status administration index and implemented audited custom-role metadata and lifecycle management.

## 2026-09-03 Creation-Origin Visibility
- University Role Management may list/control roles with `created_by_scope_type = UNIVERSITY`, including roles created by University administration on behalf of a College.
- A College route remains owner-scoped to `owner_scope_reference = college:<id>` and sees roles owned by that College regardless of whether University or College administration created them.
- College-created roles (`created_by_scope_type = COLLEGE`) must not appear in University Role Management.
