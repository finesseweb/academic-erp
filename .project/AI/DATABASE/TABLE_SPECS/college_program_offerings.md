# college_program_offerings

College-scoped operational adoption of University academic definitions. One row means one College offers one University Program Template in one Academic Session using one approved active Curriculum.

## Core Columns
- `id`
- `college_id` — required College owner; RESTRICT on delete
- `program_template_id` — required active same-University Program Template; RESTRICT on delete
- `curriculum_id` — required approved `ACTIVE` Curriculum matching the selected Program Template + Academic Session; RESTRICT on delete
- `academic_session_id` — required same-University `PLANNED|ACTIVE` Academic Session; RESTRICT on delete
- `status` — `ACTIVE|INACTIVE`; new rows start `INACTIVE` and are explicitly activated
- `created_by`, `updated_by` — nullable actor FKs; SET NULL on user delete
- timestamps

## Uniqueness
`(college_id, program_template_id, academic_session_id)` is unique. A College cannot create two Program Offerings for the same Program Template in the same Academic Session.

## Governance
- College does not duplicate Program Template or Curriculum masters.
- All academic references must belong to the College's parent University.
- Curriculum must be approved + active and must match both selected Program Template and Academic Session.
- Inactive College cannot create/update/activate/deactivate offerings.
- Deactivation is non-destructive so downstream history can remain traceable.
- Intake / Seat Capacity is stored in the child `college_program_intakes` layer. Program Offering itself remains free of seat-count fields.

## Audit
- `COLLEGE_PROGRAM_OFFERING_CREATED`
- `COLLEGE_PROGRAM_OFFERING_UPDATED`
- `COLLEGE_PROGRAM_OFFERING_ACTIVATED`
- `COLLEGE_PROGRAM_OFFERING_DEACTIVATED`

Audit scope is `COLLEGE` with the canonical `college:<id>` as scope reference.
