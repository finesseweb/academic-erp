# 2026-09-19 — College Student Account Visibility

- Added College Staff / Students switch to College Users.
- Unified Admission and Import student-account visibility through canonical Student/User/Enrollment relationships.
- Added current-session default and server-side Session -> Programme Offering -> Discipline student filters.
- Matched Student/Staff status chips to University User Management styling and casing.
- Added permission-gated Student login Enable/Disable without duplicating accounts.
- Follow-up ADR 204C removes Student email reset-link action and replaces it with Generate New Temporary Password using the existing one-time credential infrastructure. College Staff reset-link behavior remains unchanged.
- No database change.
