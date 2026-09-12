# ADR 136 — Application System Category Auto-Resolution + General Sentinel

**Status:** Accepted / Implemented  
**Date:** 2026-09-07

## Decision
Candidate Reservation Category at Seat Allocation must resolve first from the submitted Admission Application answer whose form field has `system_purpose = CANDIDATE_RESERVATION_CATEGORY`. The older College Admission Form Mapping field remains a backward-compatible fallback only.

The system-mapped candidate category field is a candidate-classification field, not a physical seat-bucket definition. Its runtime options are therefore:

- synthetic `General / Unreserved` (`GENERAL`), and
- ACTIVE `VERTICAL` Reservation Categories from the University master.

Horizontal categories are excluded. General/Open-like master rows are also excluded from the mapped field to avoid duplicate semantics and to prevent a `GEN` candidate classification from being interpreted as a reserved Intake quota.

## Seat Allocation behavior
- A valid application category answer is auto-resolved and locked in the allocation dialog.
- Manual candidate-category confirmation appears only when no valid system-mapped/mapped application value can be resolved.
- `GENERAL` remains candidate classification only; the physical open-merit bucket remains `OPEN` / null reservation category.
- Reserved candidates may still consume OPEN according to ADR 135 merit/capacity rules; their candidate category remains unchanged.
- Candidate category and physical seat category remain separate snapshots.

## Compatibility
Existing `reservation_category_field_id` mapping remains supported for older forms. No migration is required by this ADR.
