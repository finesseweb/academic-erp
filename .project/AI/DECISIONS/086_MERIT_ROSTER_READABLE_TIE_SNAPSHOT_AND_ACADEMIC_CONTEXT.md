# ADR 086 — Merit/Roster readable tie snapshot and academic context

## Decision
The Merit / Roster screen must present tie-break values in a human-readable form and must explicitly expose the academic context of the selected Selection Rule: Degree Level, Degree, and Discipline.

- `APPLICATION_SUBMITTED_AT` remains stored/compared as the existing Unix timestamp value for deterministic ranking, but UI renders it as a localized date-time instead of raw epoch seconds.
- `DATE_OF_BIRTH` renders as a readable date.
- Tie direction renders semantically (Earlier/Later, Older/Younger, Higher/Lower) rather than raw ASC/DESC.
- Degree Level and Degree are resolved from Program Template -> Degree -> Degree Level.
- Discipline is resolved from the exact Intake bucket's discipline allocation. Program-level buckets show `Program-wide / Not fixed` because they are not bound to one discipline.
- Ranking algorithm, locked Selection Rule, tie-break comparison values, generated snapshots, and database schema remain unchanged.
