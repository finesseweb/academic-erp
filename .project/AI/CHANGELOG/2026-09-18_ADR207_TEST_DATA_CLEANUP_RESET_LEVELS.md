# 2026-09-18 — ADR 207 Test Data Cleanup Reset Levels
- Added Admission & Merit Workflow Reset.
- Preserved submitted Applications/applicant answers and academic setup while clearing generated downstream admission workflow data.
- Hardened canonical Student cleanup to remove Enrollment Course Choices before Enrollment rows.
- Full Academic Test Reset now includes IMPORT-source Students and explicitly preserves bootstrap/login access.
- DB classification: no schema change / no migration.
