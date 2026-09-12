# ADR 061 — Admission Form Field Validation and Copy Behavior

## Decision
Admission Form Builder now supports reusable field-level validation and cross-field behavior without hard-coding marks or address fields.

### Intrinsic validation
Text-like fields support minimum, maximum, or exact character length. Number fields support minimum/maximum value, whole-number-only, and decimal-place limits. Existing file size/extension rules remain unchanged.

### Cross-field validation
NUMBER fields can compare against another NUMBER field and DATE fields can compare against another DATE field using `<`, `<=`, `>`, `>=`, `=` and `!=`. Example: Obtained Marks <= Total Marks. The comparison source must be in the same or an earlier step.

### Copy behavior
A target field may copy a value from another compatible source field when a configured trigger field has one of the configured values. Example: Permanent Address fields copy Correspondence Address fields when “Permanent Address same as Correspondence?” = YES. The target can be locked while the copy rule is active.

## Persistence
Intrinsic limits continue to use `college_admission_form_fields.validation_rules`. Referential behavior is normalized into two new tables:
- `college_admission_form_field_comparisons`
- `college_admission_form_field_copy_rules`

This preserves FK integrity for source/trigger dependencies and prevents silent breakage if a referenced field is deleted.

## Runtime enforcement
Rules are enforced by `CollegeAdmissionDynamicFieldService` on the backend for both internal and public application creation/update. Public and internal React forms also apply compatible HTML constraints and live copy behavior. Backend remains authoritative.

## Lifecycle
Rules are editable only while the owning template is DRAFT, following the existing Admission Form lifecycle. ACTIVE/RETIRED templates remain structurally frozen.

## 2026-09-01 — Dynamic text content constraints

Admission Form `TEXT` and `TEXTAREA` fields may optionally define `validation_rules.text_input_mode` with one of:

- `ANY` — letters, numbers and symbols are accepted.
- `LETTERS_ONLY` — Unicode letters are accepted; spaces, apostrophes and hyphens may separate words/names.
- `DIGITS_ONLY` — only digits are accepted. This remains a text value so leading zeroes are preserved (for example PIN/ID values).
- `ALPHANUMERIC` — Unicode letters and numbers are accepted; spaces and symbols are rejected.

The rule is template-configured and is never tied to a hard-coded field name. Public applicant entry and College internal application entry provide immediate browser feedback, while `CollegeAdmissionDynamicFieldService` remains the authoritative backend enforcement. `EMAIL` and `PHONE` retain their dedicated format validation and do not expose this selector. Numeric values used for calculations (marks, percentages, totals) continue to use the `NUMBER` field type rather than `TEXT + DIGITS_ONLY`.

