# ENR-4 Enhancement QA — Imported Student Login

Status: READY FOR OWNER QA.

1. Import with Create Student Login Accounts OFF: Student + Enrollment created, `students.user_id` remains null.
2. Import with option ON and unique emails: import succeeds, each Student links to an ACTIVE STUDENT user in the same College.
3. Credential sheet: available after successful import, contains unique temporary passwords, and second download returns 404 because the file is one-time.
4. Missing email with option ON: entire import blocked before transaction.
5. Existing/duplicate user email with option ON: entire import blocked; no partial Students/Users created.
6. Login using generated credential: first login redirects to Change Temporary Password.
7. Wrong temporary password on change screen: rejected. Correct current password + valid confirmed new password succeeds and clears `must_change_password`.
8. Subsequent login: goes directly to Student Portal.
9. Admission-origin Student: existing applicant account continuity still opens the same canonical Student Portal.
10. Audit `student.import.completed` records count of login accounts only, never passwords.

Regression: Student Profile, Identity, Enrollment, Admission, Fee, Curriculum, and Import academic/profile mappings must remain unchanged.

8. Miss the/download-delete credential sheet, then use Credential Recovery with the imported student's email: a replacement one-time CSV is created.
9. Confirm the old temporary password no longer authenticates, the new temporary password does, and first login still forces password change.
10. Recovery for a non-IMPORT student, another College's student, unlinked student, or non-STUDENT account is rejected without changing credentials.
11. Confirm audit event `student.import.login_credential_regenerated` contains IDs/state only and never plaintext credentials.
