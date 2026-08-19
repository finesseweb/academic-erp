# Table Specification: Roles

## Identity
- Physical table / Laravel migrations / Eloquent model: `roles` / `Role`
- Domain: Authorization
- Scope/grain: one row per reusable permission bundle; globally or Affiliated-College-owned

## Keys and Columns
- PK: unsigned BIGINT `id`; globally unique stable `code`.
- `is_system_role` protects system templates such as `SUPER_ADMIN`; custom roles use the same table.
- `owner_scope_type` + `owner_scope_reference` identifies role ownership, not assignment scope.
- Status and `created_at`/`updated_at` support non-destructive lifecycle management.

## Relationships and Indexes
- Parent of `role_permissions` and `user_roles`, both RESTRICT on delete.
- `(owner_scope_type, owner_scope_reference, status)` supports scoped role lists.

## Rules
- System roles are not deletable or unsafe to rename through normal workflows.
- STUDENT and PARENT fit as future role records; no role-specific user columns belong here.

## Change History
- 2026-08-13: Created; seeded protected global `SUPER_ADMIN`.
- 2026-08-18: Repository migration now persists the protected global `SUPER_ADMIN` and grants the two implemented University Profile permissions. Other catalog grants remain milestone-gated.
- 2026-08-19: Added the owner-scope/status administration index and implemented audited custom-role metadata and lifecycle management.
