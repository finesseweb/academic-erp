# ADR 204B — College Student Account Visibility

## Status
Implemented for QA — revised 2026-09-19.

## Decision
College Users keeps `College Staff | Students` as one administration surface. Student accounts are derived only from canonical `Student -> User` plus active `StudentEnrollment` relationships; Admission and Import are never split by source type.

The Students view defaults to the University's current ACTIVE Academic Session and supports server-side Session -> Programme Offering -> Discipline -> Search filtering. Programme Offering and Discipline default to All within the selected session. Pagination remains server-side.

Account status must use the same status-chip presentation as University User Management (`Active` / `Inactive` with the same classes), not a College-specific status style.

College administrators may manage login access from the Student row using the existing College user access permissions: enable/disable sign-in and generate a one-time temporary password. ADR 204C supersedes the earlier Student reset-link action; College Staff reset-link behavior is unchanged. These actions operate on the already-linked canonical `users` record; they do not create a second Student account, change academic enrollment status, or branch on Admission vs Import provenance. Student Profile remains the academic-record action.

## Database impact
None. No tables, columns, FKs, indexes, reference data, or migration are added/changed.
