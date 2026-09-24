# ENR-4.8 — Offered-From Admission / Import Parity
Date: 2026-09-17
Status: Implemented; Owner QA pending

## Correction
Supersedes the ENR-4.6 user-facing rule that genuine Import choices should contain Curriculum Course Codes. The owner-approved workflow selects Offered From; the linked course mapping is internal.

## Code
- `ApplicantAcademicPreferenceService`: Import schema emits dynamic `OFFERED_FROM` sockets from Curriculum min/max. CSV Offered From code/name resolves to exactly one active mapping; ambiguity is rejected. Existing package resolution is preserved.
- Public Admission Application: non-package choice cards display only Offered From options. Internal checkbox values remain Curriculum Course Mapping IDs. Review shows Offered From rather than internal course code/name.

## Persistence
No schema change. Application and Import continue to store actual resolved Curriculum Course Mapping IDs/courses.

## QA gate
Use the same Programme Offering/Curriculum in Admission and Import. For the same Offered From selections, verify the same mapping IDs resolve and later Enrollment contains those mappings. Test min/max 1, 2, 3; Common/Interdisciplinary; invalid Offered From; duplicate; ambiguous configuration; package mode.
