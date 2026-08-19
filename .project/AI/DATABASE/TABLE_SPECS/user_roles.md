# Table Specification: User Roles

## Identity
- Physical table / Laravel migrations / Eloquent model: `user_roles` / `UserRole`
- Scope/grain: explicit authorization scope; one row per user-role-scope assignment

## Keys and Relationships
- PK: unsigned BIGINT `id`.
- Unique (`user_id`, `role_id`, `scope_type`, `scope_reference`) prevents duplicate assignments.
- Required user/role FKs use RESTRICT delete.

## Scope and Lifecycle
- Canonical global scope: `GLOBAL` + `global`.
- Future Affiliated College scope: `COLLEGE` + `College:<stable-id>`; narrower future resource scopes use the same extensible pattern.
- Status plus effective dates supports revocation/expiry; DB check ensures end is not before start.
- Laravel stores selected effective-from dates at start-of-day and effective-until dates at end-of-day so the final selected date is inclusive.
- Runtime permission resolution ignores inactive, not-yet-effective and expired assignments.

## Indexes / Access
- User/effective index supports permission resolution.
- Role/status supports assigned-user counts.
- Scope/status supports tenant-scoped assignment lookup.
- User/status/effective-date supports runtime authorization filtering.
- Never combine permissions from one assignment with another assignment's scope.

## Change History
- 2026-08-13: Created; seeded one global SUPER_ADMIN assignment.
- 2026-08-20: Added role/status and scope/status indexes plus validated University/College assignment and audited removal workflows.
- 2026-08-20: Added effective-access index, scope/status/date editing, and runtime effective-period enforcement.
