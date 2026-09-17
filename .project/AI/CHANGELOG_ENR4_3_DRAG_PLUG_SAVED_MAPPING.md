# ENR-4.3 — Drag-and-Plug + Saved Mapping Templates — 2026-09-17

Status: IMPLEMENTED / OWNER QA PENDING

- Replaced separate search + dropdown mapping controls with a visual drag-and-plug mapper.
- CSV headers can be connected once only; connected headers are locked and backend duplicate validation remains authoritative.
- Preserved exact-header automatic suggestions.
- Added named saved mappings scoped to College + Programme Offering.
- Multiple mapping variants are supported by distinct names; same-name save updates the existing mapping.
- Added Load Mapping and per-mapping Download Template.
- Saved template CSV header is generated from the saved mapping.
- Added supporting `student_import_mappings` configuration table; no new Student domain table.
- Fixed preview redirect to preserve `offering_id`, keeping Programme-aware Student Profile targets visible after validation.
- QA remains pending; ENR-4 is not closed.
