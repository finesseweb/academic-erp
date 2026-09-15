# `college_admission_form_field_scopes`

Stores relational academic applicability for one dynamic Admission Form field.

Nullable scope columns:
- `degree_level_id`
- `degree_id`
- `program_template_id`
- `college_program_offering_id`
- `curriculum_id`
- `college_admission_cycle_id`

No scope row means the field applies to all Applications resolved to the template. Within a scope row, every populated column must match the Application's linked academic context. Multiple rows are structurally supported as OR alternatives; each row is internally an AND match.

The field relationship cascades on field deletion; academic references use RESTRICT to prevent silent historical retargeting.
