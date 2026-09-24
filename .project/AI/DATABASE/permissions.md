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

- 2026-09-16: ADR 197 migration `2026_09_16_080000_register_fee_clearance_permission.php` registers active, non-sensitive, College-delegable `college_fee_clearance.view` (`module=Fee Management`, `resource=college_fee_clearance`, `action=view`). The migration also synchronizes protected default role grants through existing `role_permissions`; no authorization schema change is introduced.

### ENR-1 registration — 2026-09-16
`college_student_enrollment.view` is registered by `2026_09_16_153000_register_student_enrollment_view_permission.php`. It is College-delegable and read-only; default active grants are SUPER_ADMIN and COLLEGE_ADMIN.

## ENR-3 / ADR 203
- `college_student_identity.manage` — sensitive, College-delegable permission for numbering-rule configuration and identity assignment. Default grants: SUPER_ADMIN and COLLEGE_ADMIN.
- Student Identity read access reuses `college_student_enrollment.view` and remains College-scoped.

## ENR-3.6 permission reference-data migration
Migration `2026_09_17_120000_align_student_management_rbac` normalizes Student Enrollment + Student Identity permission rows to module `Student Management` and registers `college_student_identity.view`. This is RBAC/reference data only; no new domain table/column.

## ENR-4 Student Import / Migration — 2026-09-17
- `college_student_import.view` — Student Management; College-delegable; view import/migration workspace and download template.
- `college_student_import.manage` — Student Management; College-delegable; sensitive; upload, validate and commit Student imports.
Migration `2026_09_17_130000_enable_student_import_migration` registers both and grants defaults to active SUPER_ADMIN/COLLEGE_ADMIN roles. No new permission table/domain table.
