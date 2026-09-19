# ADR 204A — Imported Student Login Provisioning

## Status
Implemented for QA — 2026-09-19.

## Decision
Student Import may optionally provision login access. The import remains canonical: it creates Student + Student Enrollment first, then links one `users` account through `students.user_id`. It never creates a fake Applicant, Application, Admission, or Fee record.

When **Create Student Login Accounts** is selected, every row requires a unique valid email. Existing `users.email` values block account provisioning rather than being silently linked. Each account receives `account_type=STUDENT`, the imported student's College as `primary_college_id`, ACTIVE status, and a cryptographically generated per-student temporary password. `users.must_change_password=true` forces replacement on first standard login.

The credential CSV is generated only after a successful transaction, is restricted to the authorized College user, uses `Cache-Control: no-store`, and is deleted after the first download. Plain temporary passwords are never stored in the database or audit log.

If the one-time credential is missed or lost, an authorized College user may regenerate a credential for an IMPORT-origin student by email. Regeneration never reveals the previous password: it creates a new cryptographically generated temporary password, immediately invalidates the previous password, restores `must_change_password=true`, emits `student.import.login_credential_regenerated`, and exposes the replacement only through another one-time credential CSV.

Student Portal resolution is source-independent: `User -> Student -> Enrollment`. ApplicantProfile is no longer required to enter the Student Portal, preserving Admission-origin continuity while allowing IMPORT-origin students to use the same portal.

## Non-goals
This does not implement the future Student Account Management hierarchy, bulk credential regeneration, email delivery, or College/University drill-down management. Credential recovery here is intentionally a single imported-student safety path inside Student Import.
