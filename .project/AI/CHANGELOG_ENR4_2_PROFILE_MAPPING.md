# ENR-4.2 — Programme-aware Student Profile Mapping
Date: 2026-09-17
Status: IMPLEMENTED — OWNER QA PENDING

- Added Programme Offering-aware dynamic Student Profile targets from Admission Form configuration.
- Respects `STUDENT_PROFILE` policy and academic field scopes.
- Persists imported dynamic values into existing `student_profile_values`.
- Added searchable CSV-column mapping controls.
- Selected CSV columns disappear from other mapping controls; backend also rejects duplicate column use.
- Retains ENR-4.1 Laravel-compatible CSV/TXT upload validation.
- No schema migration and no new domain table.
