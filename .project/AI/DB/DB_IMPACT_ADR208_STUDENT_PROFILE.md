# DB Impact — ADR 208 / ENR-6 Student Profile

## Classification
REFERENCE/RBAC DATA CHANGE ONLY. No Student-domain table, column, FK, index, or constraint is added.

## Existing authoritative tables consumed
- `students`: permanent Student core data; ENR-6 may update `full_name`, `date_of_birth`, `email`, `phone`, `updated_by`.
- `student_profile_values`: Student-owned governed dynamic values; ENR-6 updates existing rows only. Unique ownership remains (`student_id`,`profile_key`).
- `student_enrollments`: read-only canonical academic context.
- `student_enrollment_course_choices`: read-only canonical course choices.
- `college_admission_form_fields`: governance/type metadata only; only `STUDENT_PROFILE` values are editable.
- `audit_logs`: records `student.profile.updated`.

## Migration
`2026_09_18_180000_register_student_profile_permissions.php` registers `college_student_profile.view` and `college_student_profile.edit` and grants both to active SUPER_ADMIN/COLLEGE_ADMIN roles. It is restart-safe through `updateOrInsert`. Down removes role-permission links before permission rows.

## Invariants
Student Identity and Enrollment academic context are not modified by Student Profile. No Admission/Import-specific academic resolution is allowed.

## ENR-6.1 DB impact
No schema or data migration. Existing `student_profile_values.value_text = GENERAL` remains canonical for General / Unreserved. Reservation Category master rows remain authoritative for non-general ACTIVE VERTICAL categories. `student_enrollments.curriculum_id` remains the canonical FK; UI resolves it to `curricula.name/code` without changing persistence.

## ENR-6.2 impact
No schema/reference-data migration. Existing `student_profile_values.file_*` columns own the Student-profile photo snapshot/reference. Admission-origin file paths are preserved as provenance when a Student photo is later replaced. Canonical academic presentation is read-only from existing Enrollment/Curriculum mapping tables; no academic rows are changed.

## ENR-6.3 impact
No schema/reference-data migration. For an Import-source Student, the first Student Profile photo upload may create the existing canonical `student_profile_values` row (`student_id`, authoritative `source_application_field_id`, configured `profile_key`, label snapshot and file metadata). The existing unique (`student_id`,`profile_key`) invariant remains authoritative; subsequent photo updates reuse that row. CSV import still excludes FILE/IMAGE mappings.

## ENR-6.4 DB impact
No schema, FK, index, constraint, permission/reference-data, or data migration change. The correction is resolver-only: it restores authoritative Degree context before resolving the existing Admission Form mapping and existing `student_profile_values` photo lifecycle.
