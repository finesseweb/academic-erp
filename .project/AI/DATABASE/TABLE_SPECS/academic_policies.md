# Table Spec — academic_policies

Versioned University Academic Policy header.

Key relations:
- university_id -> universities
- academic_session_id -> academic_sessions
- degree_level_id -> degree_levels (nullable; required only when scope_type = DEGREE_LEVEL)
- program_template_id -> program_templates (nullable by scope)
- curriculum_id -> curricula (nullable by scope)
- parent_policy_id -> academic_policies (revision lineage)
- superseded_by_id -> academic_policies (replacement pointer)

Lifecycle:
- DRAFT / ACTIVE / RETIRED
Approval state:
- NOT_SUBMITTED / SUBMITTED / UNDER_APPROVAL / RETURNED / REJECTED / APPROVED

Phase 1 uses DRAFT management and validation. Approval engine wiring is the next gate.


## Scope Contract — 2026-08-25
Allowed `scope_type` values:
- `UNIVERSITY` — University-wide policy; degree_level_id, program_template_id and curriculum_id must be null.
- `DEGREE_LEVEL` — policy for one active same-University Degree Level; degree_level_id required and program_template_id/curriculum_id null.
- `PROGRAM_TEMPLATE` — policy for one same-University Program Template; program_template_id required and degree_level_id/curriculum_id null.
- `CURRICULUM` — policy for one current ACTIVE + APPROVED Curriculum in the selected Academic Session; curriculum_id required, degree_level_id null, and program_template_id may be used only when it matches the Curriculum.

The scope hierarchy is `UNIVERSITY -> DEGREE_LEVEL -> PROGRAM_TEMPLATE -> CURRICULUM`. Degree Level scope references the existing Degree Level Master; no duplicate free-text undergraduate/postgraduate field is introduced.

## Physical DB migration note — 2026-08-25
The existing MySQL `scope_type` column was originally an ENUM containing only `UNIVERSITY`, `PROGRAM_TEMPLATE`, and `CURRICULUM`. Adding Degree Level scope therefore requires both the nullable `degree_level_id` foreign key **and** expansion of the physical ENUM to include `DEGREE_LEVEL`. The follow-up migration `2026_08_25_061500_expand_academic_policy_scope_type_enum.php` performs this expansion. Do not treat a frontend/request validation change alone as sufficient.
