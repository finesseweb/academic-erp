# 2026-09-19 — ENR-4.6C Student Temporary Credential Action

- Removed Student `Send Password Reset Link` behavior from College Users -> Students.
- Reused the established temporary-password/mandatory-change/one-time-credential infrastructure for unified canonical Student accounts.
- Added `student.account.temporary_password_regenerated` audit semantics for the College Student account action.
- Preserved College Staff email reset-link behavior.
- Preserved current-session and Session -> Programme Offering -> Discipline Student filters, account status consistency and login Enable/Disable behavior.
- Documented future Student Portal information architecture without changing the existing implementation phase hierarchy.
- No database schema or migration change.
