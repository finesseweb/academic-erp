# curricula

University-owned versioned Curriculum Header. This table stores only the curriculum identity/version/lifecycle layer. Terms, semesters, slots, credits and Course / Paper Mapping are intentionally excluded until the subsequent Manage Structure milestone.

## Core Columns
- `id`
- `university_id` — required University owner; RESTRICT on delete
- `program_template_id` — required active same-University Program Template; RESTRICT on delete
- `academic_session_id` — required active same-University Academic Session; RESTRICT on delete
- `code` — unique within the University
- `name`
- `version`
- `effective_from` — optional
- `effective_to` — optional and cannot precede `effective_from`
- `lifecycle_status` — `DRAFT|ACTIVE|RETIRED`
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
