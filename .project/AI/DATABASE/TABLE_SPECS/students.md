# Table: `students`

## Purpose
Authoritative College-owned Student master created by approved Admission conversion or controlled legacy import. It stores stable identity/core profile data; session/programme/batch/section membership belongs to `student_enrollments`.

## Columns
- `id` — PK.
- `college_id` — required College scope.
- `user_id` — nullable unique User identity. Required for Applicant promotion; nullable for legacy imports until portal provisioning.
- `admission_id` — nullable unique Admission provenance.
- `college_admission_application_id` — nullable unique Application provenance; retained for traceability and existing cleanup protection.
- `source_type` — `ADMISSION|IMPORT`.
- `student_uid` — nullable, College-unique when present; generation/format is deferred to ENR-3.
- `full_name` — canonical display/legal name available at enrollment/import.
- `date_of_birth`, `email`, `phone` — stable core identity/contact values.
- `status` — `ACTIVE|INACTIVE`.
- `created_by`, `updated_by`, timestamps.

## Rules
- Do not add arbitrary Admission-form fields as columns.
- Applicant-originated Student reuses the existing User; no duplicate login.
- Imported Student may initially have no User.
- Programme/session placement is never inferred from Student alone.

## ENR-2 write behavior
ENR-2 is the first production writer for Admission-sourced Student rows. It copies only canonical Application identity/contact fields, preserves `source_type=ADMISSION`, links Admission/Application and existing Applicant user, and intentionally leaves `student_uid` NULL for ENR-3.

## ENR-3 / ADR 203
- `university_roll_no` — nullable until identity assignment; College-unique when present; Student-owned and never silently renumbered.
- `student_uid` generation is now owned by ADR 203; College-unique and assigned once.
