# Table Specification: Permissions

## Identity
- Physical table / Laravel migrations / Eloquent model: `permissions` / `Permission`
- Domain/scope/grain: global authorization catalog; one row per implemented `resource.action` capability

## Keys and Columns
- PK: unsigned BIGINT `id`.
- Unique stable `code`; unique (`resource`, `action`) prevents duplicate capability definitions.
- Module grouping, description, sensitive flag, status and timestamps.

## Relationships and Indexes
- Parent of `role_permissions`; delete restricted.
- `(module, status)` supports catalog grouping/filtering.

## Rules
- Permission rows are application capabilities, not arbitrary user-created labels.
- Disable rather than delete a permission referenced by history/configuration.

## Change History
- 2026-08-20: Added `is_college_delegable` allow-list metadata and its status/module lookup index; College administrators cannot delegate permissions without this flag.
- 2026-08-13: Created and seeded initial platform permissions.
- 2026-08-13: Added `theme.select_own` and `theme.manage_personal_selection`; granted both to the global `SUPER_ADMIN` system role.
- 2026-08-18: Current Laravel repository persists `university.view` and `university.update` for the University Profile milestone. Earlier catalog claims must be verified during their respective milestones.
- 2026-08-19: Added `permission.view`, the module/status index, and the read-only searchable catalog UI. Permission creation remains migration/code-controlled.
