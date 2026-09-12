# college_admission_form_fields

Dynamic field definitions under a Form Step. Supports TEXT, NUMBER, DATE, EMAIL, PHONE, TEXTAREA, SELECT, RADIO, CHECKBOX, MULTISELECT, FILE, IMAGE, YES_NO. Required/optional, display order, validation JSON and visibility JSON are configuration data. Core Admission linkage is not stored here.

## Conditional/applicability extension — 2026-08-27

- `condition_match_mode` — `ALL|ANY`, default `ALL`.
- Answer dependencies are normalized in `college_admission_form_field_conditions` rather than embedding business links in labels.
- Academic applicability is normalized in `college_admission_form_field_scopes`.
- `is_required` means required only after both academic applicability and answer-condition visibility evaluate true.
