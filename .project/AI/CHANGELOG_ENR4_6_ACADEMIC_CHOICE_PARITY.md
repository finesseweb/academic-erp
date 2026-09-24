# ENR-4.6 — Academic Choice Parity — 2026-09-17

- Replaced pipe-separated multi-course import input with dynamic one-course-per-choice mapping sockets.
- Choice socket cardinality is generated from Curriculum slot `min_selection` / `max_selection`.
- Preserved Admission Form academic-package behavior for complete Offered From/Common packages.
- Genuine choices resolve actual Curriculum Course Codes to authoritative Curriculum Course Mapping IDs.
- Added duplicate, unavailable/wrong-category, missing-count and stale-curriculum safeguards through the existing academic preference resolver.
- Documented saved-mapping staleness after curriculum cardinality changes.
- DB impact: none; no migration.
- QA: pending owner end-to-end verification.
