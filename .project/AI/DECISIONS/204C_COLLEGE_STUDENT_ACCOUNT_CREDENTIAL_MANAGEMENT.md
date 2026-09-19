# ADR 204C — College Student Account Credential Management

## Status
Implemented for Owner QA — 2026-09-19.

## Decision
College Users -> Students manages the login account already linked to the canonical Student. Student academic access is not assigned student-by-student from this screen.

The Student row account actions are:
- View Profile.
- Generate New Temporary Password.
- Enable / Disable Login.

The previous Student **Send Password Reset Link** action is removed from the Student surface. College Staff password-reset behavior is unchanged.

Generate New Temporary Password reuses the same secure credential-generation infrastructure already used by Student Import credential recovery. It works against the canonical `Student -> User` relationship for both Admission-origin and Import-origin students in the current College; account management does not branch on enrollment provenance. The existing password becomes invalid immediately, `users.must_change_password` becomes true, and the student must replace the temporary password on next login. Plaintext is not persisted in the database or audit log. The generated credential is exposed through the existing actor-scoped, one-time credential CSV download.

The mutation requires `college_user.reset_password`. Credential download accepts either Student Import manage permission or College User reset-password permission, while the stored credential token remains scoped to the actor and College.

The audit event for this unified College Student account action is `student.account.temporary_password_regenerated`. Existing Student Import recovery retains `student.import.login_credential_regenerated` so historical/import-specific audit semantics remain stable.

Enable/Disable Login affects only the linked User account status. It never changes Student or Student Enrollment academic status.

## Database impact
None. Existing `users.password`, `users.must_change_password`, Student/User linkage and append-only audit infrastructure are reused. No table, column, FK, index, constraint, reference-data or migration change is required.
