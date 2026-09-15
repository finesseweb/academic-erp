# `college_admission_form_field_conditions`

Stores answer-based visibility conditions for dynamic Admission Form fields.

- `college_admission_form_field_id` → target field.
- `source_field_id` → source dynamic field whose answer is evaluated.
- `operator` → `EQUALS|NOT_EQUALS|IN|NOT_IN|CONTAINS|IS_EMPTY|IS_NOT_EMPTY`.
- `compare_values` → JSON array of comparison values; empty for empty/not-empty operators.
- `display_order`, `is_active` → deterministic evaluation metadata.

Target-field delete cascades. Source-field delete is restricted so a live dependency cannot be silently orphaned.
