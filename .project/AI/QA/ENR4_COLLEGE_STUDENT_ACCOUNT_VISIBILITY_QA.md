# ENR-4 QA — College Student Account Visibility

Status: OWNER QA IN PROGRESS — account visibility/filter/status QA accepted; ADR 204C temporary-password action pending final Owner QA
Date: 2026-09-19

Verify:
- College Users defaults to College Staff; staff actions remain unchanged.
- Students contains both ADMISSION and IMPORT students linked to this College; another College never leaks into the list.
- Students defaults to the current ACTIVE Academic Session.
- Session -> Programme Offering -> Discipline filters constrain the server-side result set; Programme Offering and Discipline may remain All.
- Search works for name/email/Student UID/University Roll/Class Roll and pagination preserves filters.
- Student account status visually matches University User Management exactly: Active/Inactive chip styling and casing.
- Enable/Disable Login changes only the linked User access status, not Student/Enrollment academic status.
- Generate New Temporary Password uses the linked canonical User account for either Admission- or Import-origin Students, invalidates the previous password immediately, sets mandatory first-login password change, and exposes the new credential only through the actor-scoped one-time CSV.
- Confirm the Student row no longer shows Send Password Reset Link. College Staff reset-link behavior remains unchanged.
- Confirm `student.account.temporary_password_regenerated` is written without plaintext credentials.
- View Profile remains permission-gated and opens the canonical Student Profile.
