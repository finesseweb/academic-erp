# 2026-09-19 — ENR-4 Imported Student Login Provisioning

- Added optional Create Student Login Accounts at final import confirmation.
- Added per-student random temporary passwords and one-time credential CSV download.
- Added first-login forced password replacement.
- Changed Student Portal lookup from ApplicantProfile to canonical Student.user_id, enabling source-independent Admission/Import portal access.
- Added `users.must_change_password`; no Student-domain schema duplication.
- Existing emails block provisioning instead of unsafe automatic account linking.

- Added single-student Credential Recovery for missed/lost one-time import credentials. Recovery rotates the temporary password rather than revealing/storing the old one.
- Added audit event `student.import.login_credential_regenerated`; plaintext passwords remain excluded from DB/audit logs.
