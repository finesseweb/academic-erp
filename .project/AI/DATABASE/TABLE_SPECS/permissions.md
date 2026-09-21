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

## ENR-2 addition — 2026-09-16
`college_student_enrollment.enroll` — sensitive, College-delegable mutation permission for converting an eligible confirmed Admission into Student Enrollment. Registered by `2026_09_16_163000_register_student_enrollment_enroll_permission.php`; default protected grants: SUPER_ADMIN, COLLEGE_ADMIN.

## ENR-3.6 rows
Student Management contains `college_student_enrollment.view`, `college_student_enrollment.enroll`, `college_student_identity.view`, and `college_student_identity.manage`. The migration changes permission metadata/reference rows only; the `permissions` table schema is unchanged.

## 2026-09-20 — Course Delivery permission rows
Migration `2026_09_20_080000_create_course_offerings_table.php` registers `college_course_offering.view`, `.create`, `.enable`, and `.disable` under module `Course Delivery`, resource `college_course_offering`. All are College-delegable; enable/disable are sensitive.
## 2026-09-21 — Faculty Allocation permissions

Migration `2026_09_21_080000_create_faculty_allocations_table.php` registers five management permissions plus College-delegable `college_faculty_allocation.eligible` under Course Delivery. Enable and disable are sensitive; eligibility is not a management grant.

Migration `2026_09_21_090000_register_faculty_allocation_eligibility_permission.php` is the deployment repair for environments where the Faculty Allocation table migration ran before the eligibility permission was introduced. It idempotently registers the missing active College-delegable permission without granting it to administrator roles.
