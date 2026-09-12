# college_admission_form_field_options

## Purpose
Stores selectable options for dynamic Admission Form fields such as SELECT, RADIO, CHECKBOX and MULTISELECT.

## Ownership / relationship
- Parent: `college_admission_form_fields.id`
- FK: `college_admission_form_field_id`
- On parent delete: cascade

## Important columns
- `value` — stable machine identifier used by saved answers/conditions.
- `label` — administrator/applicant-facing display text.
- `display_order` — option ordering within the field.
- `is_active` — option lifecycle flag.

## Integrity
Unique key `caffo_field_value_uq` protects `(college_admission_form_field_id, value)`.

Option values must be generated through the shared Admission Form option service rather than direct slugging. Symbol/punctuation normalization can cause collisions, so the service preserves common symbolic meaning and applies a deterministic suffix when necessary. Exact duplicate labels are rejected before persistence with a controlled validation message. The unique database key remains the final integrity boundary.
