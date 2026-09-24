# ENR-4.9 — Candidate/Application Offered-From Parity — 2026-09-17

## Fixed
- Internal Applications / Candidate Eligibility academic CHOICE UI now shows Offered From options instead of course code/name as the primary choice.
- Selected Offered From still resolves to/submits the existing `curriculum_course_mapping_id`, preserving Applicant Admission Form persistence semantics.
- Existing Academic Package behavior is preserved.
- Ambiguous Offered-From-to-course setup is blocked instead of guessed.

## Documentation
- Added permanent Public Applicant + Candidate/Application + Student Import academic parity gate.
- DB impact: none; no migration.
- ENR-4: OWNER QA IN PROGRESS.
