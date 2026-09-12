# curricula

University-owned versioned Curriculum Header. This table stores only the curriculum identity/version/lifecycle layer. Terms, semesters, slots, credits and Course / Paper Mapping are intentionally excluded until the subsequent Manage Structure milestone.

## Core Columns
- `id`
- `parent_curriculum_id` — nullable self-FK to the approved version being amended; RESTRICT on delete
- `university_id` — required University owner; RESTRICT on delete
- `program_template_id` — required active same-University Program Template; RESTRICT on delete
- `academic_session_id` — required active same-University Academic Session; RESTRICT on delete
- `code` — unique within the University
- `name`
- `version`
- `effective_from` — optional
- `effective_to` — optional and cannot precede `effective_from`
- `lifecycle_status` — `DRAFT|ACTIVE|RETIRED`
- `approval_status` — approval execution state
- `revision_type` — nullable controlled amendment classification
- `revision_reason` — nullable for normal independent curricula; required by amendment creation
- `revision_effective_from` — optional amendment effective date
- `description` — optional
- `created_by`, `updated_by` — nullable actor references; SET NULL on user deletion
- timestamps

## Keys / Indexing
- unique `(university_id, code)`
- unique `(university_id, program_template_id, academic_session_id, version)`
- lookup index `(university_id, program_template_id, academic_session_id, lifecycle_status)`

## Lifecycle / History
Curriculum headers are not hard-deleted. Retirement uses `RETIRED`. Activation/locking rules that affect the later structure tables remain deferred to Manage Structure.

## Audit
`CURRICULUM_CREATED`, `CURRICULUM_UPDATED`, `CURRICULUM_RETIRED` are written through the existing immutable audit architecture.

## Change History
- 2026-08-22: Curriculum Header implementation started and schema fixed for the header milestone.


## Structure Validation Checkpoint — 2026-08-22
Added approval-gate metadata:
- `structure_validation_hash` — nullable SHA-256 fingerprint of the last PASSed Curriculum header/structure.
- `structure_validated_at` — timestamp of the last current PASS.
- `structure_validated_by` — nullable FK to `users`; validator actor.

These columns do not replace live validation. They prove that the user explicitly validated the same structure that is being submitted.

Lifecycle integrity:
- create always starts `DRAFT`
- direct form/API update cannot promote Curriculum to `ACTIVE`
- final approval controls activation


## Amendment / Version Chain — 2026-08-24
`parent_curriculum_id` creates the approved-version amendment chain.

Rules:
- root/new Curriculum: `parent_curriculum_id = NULL`
- amendment: `parent_curriculum_id = source.id`
- source Program Template and Academic Session are preserved
- amendment starts `DRAFT / NOT_SUBMITTED` with a cleared structure validation checkpoint
- complete structure is copied into independent child rows
- source remains immutable
- Current/Previous is derived from approved child existence; no `is_current_version` database flag is stored

Index:
`(parent_curriculum_id, lifecycle_status, approval_status)` supports current/previous and open-amendment checks.

Audit:
`CURRICULUM_AMENDMENT_CREATED`


## Downstream Current-Version Consumption Rule — 2026-08-25
The existing amendment design remains authoritative: Curriculum currentness is derived and `curricula` does not store a mutable `is_current_version` flag.

For downstream modules that create a NEW academic adoption/reference and require the operative Curriculum (starting with College Program Offerings):
- eligible Curriculum = `lifecycle_status = ACTIVE`
- `approval_status = APPROVED`
- same University / Program Template / Academic Session required by the consuming module
- no direct child Curriculum with `approval_status = APPROVED`

Previous approved versions remain valid historical records for already-linked data. A newer approved amendment must never silently rewrite existing foreign keys.

Future modules must explicitly document whether they need:
1. the Current Curriculum for a new operational record, or
2. the historical Curriculum already frozen on an existing record.
They must not simply list every `ACTIVE + APPROVED` version when the workflow requires the current operative version.
