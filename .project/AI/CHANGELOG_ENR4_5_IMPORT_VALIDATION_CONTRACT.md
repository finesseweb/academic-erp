# ENR-4.5 — Import Validation Contract — 2026-09-17

Status: IMPLEMENTED / OWNER QA PENDING

- Required applicable `STUDENT_PROFILE` fields now inherit `is_required` from Admission Form configuration.
- Required targets must be connected before Preview; backend enforces the same rule.
- Required profile values are validated per row; blank values invalidate the row and block import.
- Curriculum academic choice targets are marked required and remain resolved by the shared Admission academic preference service.
- Missing applicable Admission Form Template is explicitly disclosed and does not block Core + Curriculum import.
- `APPLICATION_ONLY` fields remain outside Student Import.
- No schema migration. DB impact documentation updated in the same patch.
